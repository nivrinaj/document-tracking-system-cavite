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

    /**
     * Managing SUPPORTING documents requires PHYSICAL possession:
     *  - the encoder while it's still a draft (preparing it before release), or
     *  - the person who has actually RECEIVED it (status = received and is the holder).
     * Nobody can attach while it's in transit (released/forwarded) or in a claim pool.
     */
    private function canManage(Document $document): bool
    {
        $user = auth()->user();
        if ($document->isClosed()) {
            return false;
        }
        if ($document->status === 'draft' && $document->created_by === $user->id) {
            return true;
        }

        return $document->status === 'received' && $document->current_holder_id === $user->id;
    }

    /** Add a supporting document — a required title, with an OPTIONAL PDF or captured pages. */
    public function store(Request $request, Document $document)
    {
        $this->guard();
        abort_unless($this->canManage($document), 403);

        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'max:8192'],
        ]);

        $relPath = $this->storeFileIfAny($request, $document, $request->input('title'));
        if ($relPath === false) {
            return back()->with('error', 'The file is larger than 2 MB even after compression. Please use a smaller file or fewer pages.');
        }

        $document->attachments()->create([
            'kind' => 'supporting',
            'title' => $request->input('title'),
            'file_path' => $relPath,
            'file_size' => $relPath ? Storage::disk('local')->size($relPath) : 0,
            'uploaded_by' => $request->user()->id,
        ]);

        app(\App\Services\DocumentService::class)->log($document, 'attached', $request->user(),
            remarks: 'Supporting document “'.$request->input('title').'” added.');

        return back()->with('success', 'Supporting document added.');
    }

    /** Upload (or replace) the encoder's single digital copy of the document. */
    public function storeDigitalCopy(Request $request, Document $document)
    {
        abort_unless(Document::digitalCopyEnabled(), 404);
        // Only the encoder, while the document is still active.
        abort_unless(! $document->isClosed() && $document->created_by === $request->user()->id, 403);

        $request->validate([
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'max:8192'],
        ]);
        if (! $request->hasFile('pdf') && ! $request->hasFile('images')) {
            return back()->with('error', 'Please choose a PDF or capture the document.');
        }

        $relPath = $this->storeFileIfAny($request, $document, 'Digital copy');
        if (! $relPath) {
            return back()->with('error', 'The file is larger than 2 MB even after compression. Please use a smaller file.');
        }

        // Replace any existing digital copy.
        if ($existing = $document->digitalCopy) {
            Storage::disk('local')->delete($existing->file_path);
            $existing->delete();
        }

        $document->attachments()->create([
            'kind' => 'digital_copy',
            'title' => 'Digital copy',
            'file_path' => $relPath,
            'file_size' => Storage::disk('local')->size($relPath),
            'uploaded_by' => $request->user()->id,
        ]);

        app(\App\Services\DocumentService::class)->log($document, 'attached', $request->user(),
            remarks: 'Digital copy uploaded.');

        return back()->with('success', 'Digital copy uploaded.');
    }

    /**
     * Store an uploaded PDF / captured images as a single PDF.
     * Returns the relative path, null if no file was provided, or false if too large.
     */
    private function storeFileIfAny(Request $request, Document $document, string $title): string|false|null
    {
        if (! $request->hasFile('pdf') && ! $request->hasFile('images')) {
            return null;
        }

        $dir = "attachments/{$document->id}";
        $filename = Str::uuid().'.pdf';
        $relPath = "{$dir}/{$filename}";

        if ($request->hasFile('pdf')) {
            $request->file('pdf')->storeAs($dir, $filename, 'local');
        } else {
            Storage::disk('local')->put($relPath, $this->imagesToPdf($request->file('images'), $title));
        }

        if (Storage::disk('local')->size($relPath) > 2 * 1024 * 1024) {
            Storage::disk('local')->delete($relPath);

            return false;
        }

        return $relPath;
    }

    public function download(Request $request, DocumentAttachment $attachment)
    {
        // Anyone who can view the document can open its files (toggle controls upload/UI, not access).
        $this->authorize('view', $attachment->document);

        abort_unless($attachment->file_path && Storage::disk('local')->exists($attachment->file_path), 404);

        return response()->file(
            Storage::disk('local')->path($attachment->file_path),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.Str::slug($attachment->title).'.pdf"']
        );
    }

    public function destroy(Request $request, DocumentAttachment $attachment)
    {
        $document = $attachment->document;

        if ($attachment->kind === 'digital_copy') {
            abort_unless(! $document->isClosed() && $document->created_by === $request->user()->id, 403);
        } else {
            $this->guard();
            abort_unless($this->canManage($document), 403);
        }

        if ($attachment->file_path) {
            Storage::disk('local')->delete($attachment->file_path);
        }
        $attachment->delete();

        return back()->with('success', 'Removed.');
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
