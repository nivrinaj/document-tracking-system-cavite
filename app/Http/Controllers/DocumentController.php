<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $service)
    {
    }

    /** List documents the current user is allowed to see, with filters. */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Document::with(['creator', 'currentHolder', 'division'])->visibleTo($user)->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tracking_code', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Stage groups (used by the dashboard cards) map to several statuses.
        $stages = [
            'awaiting_release' => ['draft'],
            'in_transit' => ['released', 'forwarded'],
            'in_progress' => ['received'],
            'completed' => ['archived', 'completed'],
        ];
        if (($stage = $request->input('stage')) && isset($stages[$stage])) {
            $query->whereIn('status', $stages[$stage]);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($division = $request->input('division_id')) {
            $query->where('division_id', $division);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $perPage = (int) \App\Models\Setting::get('records_per_page', 12);
        $documents = $query->paginate($perPage)->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'divisions' => Division::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorizeAction('documents.create');

        return view('documents.create', [
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'users' => $this->assignableUsers(),
        ]);
    }

    public function store(Request $request, DocumentService $service)
    {
        $this->authorizeAction('documents.create');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:100'],
            'voucher_number' => ['nullable', 'required_if:document_type,Voucher', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'assign_remarks' => ['nullable', 'string'],
            'broadcast_scope' => ['nullable', 'in:none,division,department'],
        ]);

        // Make sure a voucher number isn't already in use for this year.
        if (strtolower($data['document_type']) === 'voucher' && ! empty($data['voucher_number'])) {
            $code = \App\Models\Document::trackingCodeForVoucher($data['voucher_number']);
            if (\App\Models\Document::where('tracking_code', $code)->exists()) {
                return back()->withInput()->withErrors([
                    'voucher_number' => "A document with voucher number \"{$data['voucher_number']}\" already exists this year ({$code}).",
                ]);
            }
        }

        // Memo broadcast — distribute to everyone in the chosen scope.
        $scope = $data['broadcast_scope'] ?? 'none';
        if (in_array($scope, ['division', 'department'])) {
            $document = $service->broadcast($data, $request->user(), $scope);

            return redirect()->route('documents.show', $document)
                ->with('success', "Memo broadcast to your {$scope}. Tracking code: {$document->tracking_code}");
        }

        $document = $service->encode($data, $request->user(), $data['assignee_id'] ?? null);

        return redirect()->route('documents.show', $document)
            ->with('success', "Document encoded. Tracking code: {$document->tracking_code}");
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $document->load(['creator', 'currentHolder', 'division', 'assignees', 'logs.actor', 'logs.toUser', 'logs.fromUser']);

        return view('documents.show', [
            'document' => $document,
            'users' => $this->assignableUsers(),
            'trackUrl' => route('track.show', $document->tracking_code),
        ]);
    }

    public function edit(Document $document)
    {
        $this->authorize('update', $document);

        return view('documents.edit', [
            'document' => $document,
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'users' => $this->assignableUsers(),
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:100'],
            'voucher_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'division_id' => ['nullable', 'exists:divisions,id'],
        ]);

        $document->update($data);

        return redirect()->route('documents.show', $document)->with('success', 'Document updated.');
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted.');
    }

    /* -------------------- Workflow actions -------------------- */

    public function assign(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('assign', $document);

        $data = $request->validate([
            'assignee_id' => ['required', 'exists:users,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $service->assign($document, (int) $data['assignee_id'], $request->user(), $data['remarks'] ?? null);

        return back()->with('success', 'Document assigned.');
    }

    public function release(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('release', $document);

        $data = $request->validate(['remarks' => ['nullable', 'string']]);
        $service->release($document, $request->user(), $data['remarks'] ?? null);

        return back()->with('success', 'Document released. You can now print the QR code.');
    }

    public function receive(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('receive', $document);

        $data = $request->validate(['remarks' => ['nullable', 'string']]);
        $service->receive($document, $request->user(), $data['remarks'] ?? null);

        return back()->with('success', 'Document marked as received.');
    }

    public function forward(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('forward', $document);

        $data = $request->validate([
            'to_user_id' => ['required', 'exists:users,id'],
            'remarks' => ['required', 'string', 'min:3'],
        ]);

        $service->forward($document, (int) $data['to_user_id'], $request->user(), $data['remarks']);

        return back()->with('success', 'Document forwarded.');
    }

    public function acknowledge(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('acknowledge', $document);
        $service->acknowledge($document, $request->user());

        return back()->with('success', 'Receipt acknowledged. Thank you.');
    }

    public function archive(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('archive', $document);

        $data = $request->validate([
            'remarks' => ['required', 'string', 'min:3'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $service->archive($document, $request->user(), $data['remarks'], (bool) ($data['completed'] ?? false));

        return back()->with('success', 'Document archived.');
    }

    /* -------------------- QR + print -------------------- */

    public function qrcode(Document $document)
    {
        $this->authorize('view', $document);

        $svg = QrCode::format('svg')->size(240)->margin(1)
            ->generate(route('track.show', $document->tracking_code));

        return response($svg)->header('Content-Type', 'image/svg+xml');
    }

    public function print(Document $document)
    {
        $this->authorize('view', $document);

        $svg = QrCode::format('svg')->size(220)->margin(1)
            ->generate(route('track.show', $document->tracking_code));

        return view('documents.print', [
            'document' => $document,
            'qrSvg' => $svg,
            'trackUrl' => route('track.show', $document->tracking_code),
        ]);
    }

    /* -------------------- Helpers -------------------- */

    /** Active staff who can be assigned — limited to the encoder's department. */
    private function assignableUsers()
    {
        $user = Auth::user();

        return User::with('division')->where('is_active', true)
            ->when(! $user->can('documents.viewAll') && $user->department_id,
                fn ($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')->get();
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(Auth::user()->can($permission), 403);
    }
}
