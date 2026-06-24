<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAttachment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    private function guard(): void
    {
        abort_unless(Document::attachmentsEnabled(), 404);
    }

    /** Only someone who currently holds the document (or its encoder / an admin) may attach. */
    private function canManage(Document $document): bool
    {
        $user = auth()->user();
        if ($document->isClosed()) {
            return false;
        }

        return $document->current_holder_id === $user->id
            || $document->created_by === $user->id
            || $user->hasRole('Super Admin');
    }

    public function store(Request $request, Document $document)
    {
        $this->guard();
        abort_unless($this->canManage($document), 403);

        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],          // desktop: a PDF (<= 2 MB)
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'max:8192'],                      // mobile: captured pages
        ]);

        if (! $request->hasFile('pdf') && ! $request->hasFile('images')) {
            return back()->with('error', 'Please choose a PDF file or capture at least one page.');
        }

        $dir = "attachments/{$document->id}";
        $filename = Str::uuid().'.pdf';
        $relPath = "{$dir}/{$filename}";

        if ($request->hasFile('pdf')) {
            // Desktop: store the PDF as-is.
            $request->file('pdf')->storeAs($dir, $filename, 'local');
        } else {
            // Mobile: combine captured images into one compressed PDF.
            $pdf = $this->imagesToPdf($request->file('images'), $request->input('title'));
            Storage::disk('local')->put($relPath, $pdf);

            if (Storage::disk('local')->size($relPath) > 2 * 1024 * 1024) {
                Storage::disk('local')->delete($relPath);

                return back()->with('error', 'The captured pages are larger than 2 MB even after compression. Please capture fewer pages.');
            }
        }

        $document->attachments()->create([
            'title' => $request->input('title'),
            'file_path' => $relPath,
            'file_size' => Storage::disk('local')->size($relPath),
            'uploaded_by' => $request->user()->id,
        ]);

        app(\App\Services\DocumentService::class)->log($document, 'attached', $request->user(),
            remarks: 'Attached “'.$request->input('title').'”.');

        return back()->with('success', 'Attachment uploaded.');
    }

    public function download(Request $request, DocumentAttachment $attachment)
    {
        $this->guard();
        $this->authorize('view', $attachment->document);

        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return response()->file(
            Storage::disk('local')->path($attachment->file_path),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.Str::slug($attachment->title).'.pdf"']
        );
    }

    public function destroy(Request $request, DocumentAttachment $attachment)
    {
        $this->guard();
        $document = $attachment->document;
        abort_unless($this->canManage($document), 403);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }

    /**
     * Build a single PDF (one page per image) from captured photos, recompressing
     * each via GD (JPEG q72, capped width) so the result stays small without
     * visibly hurting readability of a scanned document.
     */
    private function imagesToPdf(array $images, string $title): string
    {
        $pages = [];
        foreach ($images as $image) {
            $data = $this->recompress($image->getRealPath());
            if ($data) {
                $pages[] = 'data:image/jpeg;base64,'.base64_encode($data);
            }
        }

        $pdf = Pdf::loadView('documents.attachment-pdf', ['pages' => $pages, 'title' => $title])
            ->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /** Downscale + JPEG-recompress an image with GD. Returns binary JPEG (or null). */
    private function recompress(string $path, int $maxWidth = 1600, int $quality = 72): ?string
    {
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => null,
        };
        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w > $maxWidth) {
            $nh = (int) round($h * ($maxWidth / $w));
            $dst = imagecreatetruecolor($maxWidth, $nh);
            // flatten transparency onto white
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        ob_start();
        imagejpeg($src, null, $quality);
        $bin = ob_get_clean();
        imagedestroy($src);

        return $bin ?: null;
    }
}
