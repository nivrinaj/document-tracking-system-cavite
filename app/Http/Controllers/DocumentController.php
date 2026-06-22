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

        $query = Document::with([
            'creator.department', 'creator.division',
            'currentHolder.department', 'currentHolder.division',
            'division', 'department',
        ])->visibleTo($user)->latest();

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

        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
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
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'divisions' => Division::orderBy('name')->get(['id', 'code', 'name', 'department_id']),
        ]);
    }

    public function create()
    {
        $this->authorizeAction('documents.create');

        $user = Auth::user();
        $types = \App\Models\DocumentType::availableFor($user->department_id);

        // Offices with the user's own department listed first (QoL).
        $departments = \App\Models\Department::orderByRaw('id = ? desc', [$user->department_id ?? 0])
            ->orderBy('name')->get();

        return view('documents.create', [
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'department_id']),
            'departments' => $departments,
            'users' => $this->assignableUsers(),
            'allUsers' => $this->recipientUsers(),
            'documentTypes' => $types,
            'voucherTypeNames' => $types->where('requires_voucher', true)->pluck('name')->values(),
            'crossDept' => \App\Models\Setting::get('allow_cross_department', '0') === '1',
            'priorityEnabled' => Document::priorityEnabled(),
            'ownDeptId' => $user->department_id,
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
            'source_department_id' => ['nullable', 'string', 'max:50'],
            'source_division_id' => ['nullable', 'exists:divisions,id'],
            'source_other' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'assign_remarks' => ['nullable', 'string'],
            'broadcast_scope' => ['nullable', 'in:none,division,department,transfer,multi'],
            'to_department_id' => ['nullable', 'required_if:broadcast_scope,transfer', 'exists:departments,id'],
            'recipient_ids' => ['nullable', 'required_if:broadcast_scope,multi', 'array'],
            'recipient_ids.*' => ['integer', 'exists:users,id'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'string', 'max:255'],
        ]);

        // Route-slip items only apply when the feature is enabled.
        if (! Document::routeItemsEnabled()) {
            unset($data['items']);
        }

        // Priority is optional; default to "normal" (and force it when the feature is off).
        $data['priority'] = Document::priorityEnabled() ? ($data['priority'] ?? 'normal') : 'normal';

        // Make sure a voucher number isn't already in use for this year.
        if (strtolower($data['document_type']) === 'voucher' && ! empty($data['voucher_number'])) {
            $code = \App\Models\Document::trackingCodeForVoucher($data['voucher_number']);
            if (\App\Models\Document::where('tracking_code', $code)->exists()) {
                return back()->withInput()->withErrors([
                    'voucher_number' => "A document with voucher number \"{$data['voucher_number']}\" already exists this year ({$code}).",
                ]);
            }
        }

        // Compose the human-readable source/origin from the picker.
        $sdept = $request->input('source_department_id');
        if ($sdept === 'external') {
            $data['source'] = $request->input('source_other') ?: 'External';
        } elseif ($sdept) {
            $dept = \App\Models\Department::find($sdept);
            $div = $request->input('source_division_id') ? \App\Models\Division::find($request->input('source_division_id')) : null;
            $data['source'] = trim(($dept?->code ?? '').($div ? ' · '.$div->name : '')) ?: null;
        } else {
            $data['source'] = $request->input('source_other') ?: null;
        }

        $scope = $data['broadcast_scope'] ?? 'none';

        // Memo broadcast — distribute to everyone in the chosen scope.
        if (in_array($scope, ['division', 'department'])) {
            $document = $service->broadcast($data, $request->user(), $scope);

            return redirect()->route('documents.show', $document)
                ->with('success', "Memo broadcast to your {$scope}. Tracking code: {$document->tracking_code}");
        }

        // Send to a hand-picked list of people (possibly across offices).
        if ($scope === 'multi') {
            $document = $service->broadcastToUsers($data, $request->user(), $data['recipient_ids'] ?? []);

            return redirect()->route('documents.show', $document)
                ->with('success', "Document sent to the selected recipients. Tracking code: {$document->tracking_code}");
        }

        // Transfer to another office's receiving pool (no specific person).
        if ($scope === 'transfer') {
            // The encoder's own office is implicitly the origin for a transfer.
            $actor = $request->user();
            $data['source'] = trim(($actor->department?->code ?? '').($actor->division ? ' · '.$actor->division->name : '')) ?: 'Internal';
            $document = $service->encode($data, $request->user(), null);
            $service->transferToOffice($document, (int) $data['to_department_id'], $request->user(), $data['assign_remarks'] ?: 'Transferred to your office.');

            return redirect()->route('documents.show', $document)
                ->with('success', "Document encoded and sent to the selected office's receiving pool. Tracking code: {$document->tracking_code}");
        }

        // Normal: assign to a specific staff in my own office (or assign later).
        $document = $service->encode($data, $request->user(), $data['assignee_id'] ?? null);

        return redirect()->route('documents.show', $document)
            ->with('success', "Document encoded. Tracking code: {$document->tracking_code}");
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $document->load([
            'creator.department', 'creator.division',
            'currentHolder.department', 'currentHolder.division',
            'division', 'department',
            'assignees.department', 'assignees.division',
            'logs.actor.department', 'logs.actor.division',
            'logs.toUser.department', 'logs.toUser.division',
            'logs.fromUser',
            'possessions',
            'items.decider',
        ]);

        $user = Auth::user();

        return view('documents.show', [
            'document' => $document,
            'users' => $this->assignableUsers(),
            'trackUrl' => route('track.show', $document->tracking_code),
            'crossDept' => \App\Models\Setting::get('allow_cross_department', '0') === '1',
            'departments' => \App\Models\Department::orderByRaw('id = ? desc', [$user->department_id ?? 0])->orderBy('name')->get(),
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
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'division_id' => ['nullable', 'exists:divisions,id'],
        ]);

        if (! Document::priorityEnabled()) {
            unset($data['priority']);
        }

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

        if ((int) $data['assignee_id'] === (int) $document->current_holder_id) {
            return back()->with('error', 'That staff member already holds this document — pick someone else.');
        }

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

        if ((int) $data['to_user_id'] === (int) $document->current_holder_id) {
            return back()->with('error', 'That staff member already holds this document — pick someone else.');
        }

        // Forwarding is ALWAYS within the same office. To move a document to another
        // office, receiving staff use "Transfer to office" (the claim-pool flow).
        $recipient = User::find($data['to_user_id']);
        $actorDept = $request->user()->department_id;
        if ($actorDept && $recipient && $recipient->department_id !== $actorDept) {
            return back()->with('error', 'You can only forward to staff within your own office. To send it to another office, use “Transfer to office”.');
        }

        $service->forward($document, (int) $data['to_user_id'], $request->user(), $data['remarks']);

        return back()->with('success', 'Document forwarded.');
    }

    public function pending(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('pending', $document);

        $crossDept = \App\Models\Setting::get('allow_cross_department', '0') === '1';

        $data = $request->validate([
            'remarks' => ['required', 'string', 'min:3'],
            'return_department_id' => [$crossDept ? 'nullable' : 'prohibited', 'exists:departments,id'],
        ]);

        // Cross-office on + an office chosen → return it there (clock resumes when they receive).
        if ($crossDept && ! empty($data['return_department_id'])) {
            $service->pendingReturn($document, (int) $data['return_department_id'], $request->user(), $data['remarks']);

            return back()->with('success', 'Document marked pending and returned to the selected office.');
        }

        $service->markPending($document, $request->user(), $data['remarks']);

        return back()->with('success', 'Document marked as pending. Its timer is paused.');
    }

    public function resume(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('resume', $document);

        $data = $request->validate(['remarks' => ['required', 'string', 'min:3']]);
        $service->resume($document, $request->user(), $data['remarks']);

        return back()->with('success', 'Work resumed. The timer is running again.');
    }

    public function reopen(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('reopen', $document);
        $service->reopen($document, $request->user(), $request->input('remarks'));

        return back()->with('success', 'Document reopened and set back to active.');
    }

    public function transfer(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('transfer', $document);

        $data = $request->validate([
            'to_department_id' => ['required', 'exists:departments,id'],
            'remarks' => ['required', 'string', 'min:3'],
        ]);

        // Can't transfer to the office that already holds it — use Forward/Assign within the office instead.
        if ((int) $data['to_department_id'] === (int) $document->department_id) {
            return back()->with('error', 'This document is already in that office. Use Forward or Assign to route it within the office.');
        }

        $service->transferToOffice($document, (int) $data['to_department_id'], $request->user(), $data['remarks']);

        return back()->with('success', 'Document transferred. The receiving staff of that office can now claim it.');
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

    public function itemDecision(Request $request, Document $document, \App\Models\DocumentItem $item, DocumentService $service)
    {
        abort_unless($item->document_id === $document->id, 404);
        abort_unless(\App\Models\Document::routeItemsEnabled(), 404);

        // Only the current holder (or an override role) may decide items.
        $user = $request->user();
        $canDecide = $user->can('archive', $document) || $user->can('forward', $document);
        abort_unless($canDecide, 403);

        $data = $request->validate([
            'status' => ['required', 'in:cleared,rejected'],
            'remarks' => [$request->input('status') === 'rejected' ? 'required' : 'nullable', 'string', 'max:500'],
        ]);

        $service->decideItem($item, $user, $data['status'], $data['remarks'] ?? null);

        return back()->with('success', $data['status'] === 'cleared' ? 'Item marked as cleared.' : 'Item rejected and flagged for return to origin.');
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

    /**
     * Staff who can be directly assigned a specific document — always limited to
     * the actor's OWN department. Sending to another office goes through that
     * office's receiving pool (see transferToOffice), never to a named person.
     */
    private function assignableUsers()
    {
        $user = Auth::user();

        return User::with('division', 'department')->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->when($user->department_id, fn ($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')->get();
    }

    /**
     * Hand-picked recipients of a multi-send (memo to named people) — limited to
     * the actor's OWN office (across its divisions), excluding the actor.
     */
    private function recipientUsers()
    {
        $user = Auth::user();

        return User::with('division', 'department')->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->when($user->department_id, fn ($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')->get();
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(Auth::user()->can($permission), 403);
    }
}
