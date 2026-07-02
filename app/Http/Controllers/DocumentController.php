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
        $isSuperAdmin = $user->hasRole('Super Admin');
        $isDeptHead = $user->isDeptHeadRole();
        $isDivisionHead = $user->isDivisionHead();

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

        if ($documentType = $request->input('document_type')) {
            $query->where('document_type', $documentType);
        }

        // Department filter: Super Admin's free choice only — everyone else is
        // implicitly their own department (no dropdown shown, nothing to trust).
        if ($isSuperAdmin) {
            if ($departmentId = $request->input('department_id')) {
                $query->where('department_id', $departmentId);
            }
        } elseif ($user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        // Division filter: Super Admin free choice; Dept/Asst Dept Head may pick
        // any division that actually belongs to their own department (validated
        // server-side, never trusted as-submitted); Division Head is hard-locked
        // to their own division; everyone else gets no division filter.
        if ($isSuperAdmin) {
            if ($division = $request->input('division_id')) {
                $query->where('division_id', $division);
            }
        } elseif ($isDeptHead && $user->department_id) {
            $division = $request->input('division_id');
            if ($division && Division::where('id', $division)->where('department_id', $user->department_id)->exists()) {
                $query->where('division_id', $division);
            }
        } elseif ($isDivisionHead && $user->division_id) {
            $query->where('division_id', $user->division_id);
        }

        // Staff filter: who currently holds the document. Super Admin can filter
        // by anyone; Dept/Asst Dept Head only their own department's staff;
        // Division Head only their own division's staff — all validated
        // server-side against the submitted user_id, never trusted blindly.
        if ($userId = $request->input('user_id')) {
            $allowed = $isSuperAdmin
                || ($isDeptHead && User::where('id', $userId)->where('department_id', $user->department_id)->exists())
                || ($isDivisionHead && User::where('id', $userId)->where('division_id', $user->division_id)->exists());
            if ($allowed) {
                $query->where('current_holder_id', $userId);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $perPage = (int) \App\Models\Setting::get('records_per_page', 12);
        $documents = $query->paginate($perPage)->withQueryString();

        // Staff options for the "Staff" filter dropdown — scoped per role.
        $staffOptions = collect();
        if ($isSuperAdmin) {
            $staffOptions = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'department_id', 'division_id']);
        } elseif ($isDeptHead && $user->department_id) {
            $staffOptions = User::where('is_active', true)->where('department_id', $user->department_id)->orderBy('name')->get(['id', 'name', 'department_id', 'division_id']);
        } elseif ($isDivisionHead && $user->division_id) {
            $staffOptions = User::where('is_active', true)->where('division_id', $user->division_id)->orderBy('name')->get(['id', 'name', 'department_id', 'division_id']);
        }

        return view('documents.index', [
            'documents' => $documents,
            'departments' => $isSuperAdmin ? \App\Models\Department::orderBy('name')->get() : collect(),
            'divisions' => ($isSuperAdmin || $isDeptHead)
                ? Division::orderBy('name')->get(['id', 'code', 'name', 'department_id'])
                : collect(),
            'staffOptions' => $staffOptions,
            'documentTypes' => \App\Models\DocumentType::where('is_active', true)->orderBy('name')->pluck('name'),
            'showDeadlineColumn' => (bool) optional($user->department)->deadline_enabled || $isSuperAdmin,
            'isSuperAdmin' => $isSuperAdmin,
            'isDeptHead' => $isDeptHead,
            'isDivisionHead' => $isDivisionHead,
            'canFilterStaff' => $isSuperAdmin || $isDeptHead || $isDivisionHead,
            'ownDepartmentId' => $user->department_id,
        ]);
    }

    public function create()
    {
        $this->authorizeAction('documents.create');

        $user = Auth::user();
        $types = \App\Models\DocumentType::availableFor($user->department_id);
        $isAccounting = (bool) optional($user->department)->is_accounting;
        $isHospital = (bool) optional($user->division)->is_hospital;

        // Offices with the user's own department listed first (QoL).
        $departments = \App\Models\Department::orderByRaw('id = ? desc', [$user->department_id ?? 0])
            ->orderBy('name')->get();

        $departmentHead = Document::departmentHeadFor($user->department_id);
        $canForwardToHead = (bool) optional($user->department)->forward_to_head_enabled
            && $departmentHead && $departmentHead->id !== $user->id;

        return view('documents.create', [
            'canForwardToHead' => $canForwardToHead,
            'departmentHeadName' => $departmentHead?->name,
            'officeCalendarDaysGate' => optional($user->department)->time_tracking_mode === 'calendar_days',
            'officeCalendarDaysDefaultWeekends' => (bool) (optional($user->department)->calendar_days_include_weekends ?? true),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'department_id']),
            'departments' => $departments,
            'users' => $this->assignableUsers(),
            'allUsers' => $this->recipientUsers(),
            'documentTypes' => $types,
            'voucherTypeNames' => $types->where('requires_voucher', true)->pluck('name')->values(),
            'deadlineTypeNames' => $types->where('requires_deadline', true)->pluck('name')->values(),
            'transmittalTypeNames' => $types->filter(fn ($t) => $t->transmittalAllowedFor($user->department_id))->pluck('name')->values(),
            'officeDeadline' => (bool) optional($user->department)->deadline_enabled,
            'crossDept' => \App\Models\Setting::get('allow_cross_department', '0') === '1',
            'priorityEnabled' => Document::priorityEnabled(),
            'ownDeptId' => $user->department_id,
            // Accounting reference data for Voucher / Payroll fields.
            'funds' => \App\Models\Fund::where('is_active', true)
                ->when($isHospital, fn ($q) => $q->where('hospital_available', true))
                ->orderBy('sort_order')->orderBy('name')->get(),
            'rcOfficeOptions' => \App\Models\ResponsibilityCenter::where('is_active', true)->where('is_hospital', false)
                ->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn ($rc) => ['value' => $rc->id, 'label' => $rc->label()])->values(),
            'rcProjectsByOffice' => \App\Models\ResponsibilityCenter::where('is_active', true)->where('is_hospital', false)
                ->with(['projects' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
                ->get()->mapWithKeys(fn ($rc) => [(string) $rc->id => $rc->projects->map(fn ($p) => ['value' => $p->id, 'label' => $p->label()])->values()]),
            'rcHospitalOptions' => \App\Models\ResponsibilityCenter::where('is_active', true)->where('is_hospital', true)
                ->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn ($rc) => ['value' => $rc->id, 'label' => $rc->label()])->values(),
            'natures' => \App\Models\NatureOfTransaction::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'isHospital' => $isHospital,
            'isAccounting' => $isAccounting,
            'rcHospitalRequired' => \App\Models\Setting::get('rc_hospital_required', '0') === '1',
        ]);
    }

    public function store(Request $request, DocumentService $service)
    {
        $this->authorizeAction('documents.create');

        // The extra accounting fields (amount/fund/OBR/nature) apply only when the
        // encoder's office has the Accounting toggle on; other offices encode a
        // Voucher/Payroll with the regular fields only. Responsibility Center
        // (office/unit + project) is always optional. The single hospital RC field's
        // required-ness is Super-Admin configurable (rc_hospital_required setting).
        $acct = (bool) optional($request->user()->department)->is_accounting;
        $isHospital = (bool) optional($request->user()->division)->is_hospital;
        $acctRule = $acct ? 'required_if:document_type,Voucher,Payroll' : 'nullable';
        $rcRule = ($acct && $isHospital && \App\Models\Setting::get('rc_hospital_required', '0') === '1')
            ? 'required_if:document_type,Voucher,Payroll'
            : 'nullable';

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:100'],
            // Accounting fields — only for offices flagged is_accounting (e.g. OPAcc).
            'fund_id' => ['nullable', $acctRule, 'exists:funds,id'],
            'amount' => ['nullable', $acctRule, 'numeric', 'min:0'],
            'obr_no' => ['nullable', $acctRule, 'string', 'max:100'],
            'responsibility_center_id' => ['nullable', $rcRule, 'exists:responsibility_centers,id'],
            'responsibility_center_project_id' => ['nullable', 'exists:responsibility_center_projects,id'],
            'nature_of_transaction' => ['nullable', $acctRule, 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'source_department_id' => ['nullable', 'string', 'max:50'],
            'source_division_id' => ['nullable', 'exists:divisions,id'],
            'source_other' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'is_transmittal' => ['nullable', 'boolean'],
            'transmittal_quantity' => ['nullable', 'required_if:is_transmittal,1', 'integer', 'min:1', 'max:9999'],
            'time_tracking_mode' => ['nullable', 'in:working_hours,calendar_days'],
            'calendar_days_include_weekends' => ['nullable', 'boolean'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'assign_remarks' => ['nullable', 'string'],
            'broadcast_scope' => ['nullable', 'in:none,head,division,department,transfer,multi'],
            'to_department_id' => ['nullable', 'required_if:broadcast_scope,transfer', 'exists:departments,id'],
            'recipient_ids' => ['nullable', 'required_if:broadcast_scope,multi', 'array'],
            'recipient_ids.*' => ['integer', 'exists:users,id'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'string', 'max:255'],
        ]);

        // Per-document calendar-days override — only honored when the encoder's
        // own office actually has the feature gated on (defense in depth; the
        // encode form only shows this choice otherwise).
        if (optional($request->user()->department)->time_tracking_mode === 'calendar_days') {
            $data['time_tracking_mode'] = ($data['time_tracking_mode'] ?? null) === 'calendar_days' ? 'calendar_days' : 'working_hours';
            $data['calendar_days_include_weekends'] = $data['time_tracking_mode'] === 'calendar_days'
                ? $request->boolean('calendar_days_include_weekends')
                : null;
        } else {
            unset($data['time_tracking_mode'], $data['calendar_days_include_weekends']);
        }

        // Route-slip items only apply when the feature is enabled.
        if (! Document::routeItemsEnabled()) {
            unset($data['items']);
        }

        // Priority is optional; default to "normal" (and force it when the feature is off).
        $data['priority'] = Document::priorityEnabled() ? ($data['priority'] ?? 'normal') : 'normal';

        // Transmittal quantity only makes sense when the toggle is on, and only
        // for a type/office combination actually allowed to use it (defense in
        // depth — the encode form already hides the toggle otherwise).
        $type = \App\Models\DocumentType::where('name', $data['document_type'])->first();
        $data['is_transmittal'] = $request->boolean('is_transmittal') && $type?->transmittalAllowedFor($request->user()->department_id);
        if (! $data['is_transmittal']) {
            $data['transmittal_quantity'] = null;
        }

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
            \App\Models\ActivityLog::record('documents.store', "Encoded a new document: {$document->title} ({$document->tracking_code}, #{$document->id})", $document);

            return redirect()->route('documents.show', $document)
                ->with('success', "Memo broadcast to your {$scope}. Tracking code: {$document->tracking_code}");
        }

        // Send to a hand-picked list of people (possibly across offices).
        if ($scope === 'multi') {
            $document = $service->broadcastToUsers($data, $request->user(), $data['recipient_ids'] ?? []);
            \App\Models\ActivityLog::record('documents.store', "Encoded a new document: {$document->title} ({$document->tracking_code}, #{$document->id})", $document);

            return redirect()->route('documents.show', $document)
                ->with('success', "Document sent to the selected recipients. Tracking code: {$document->tracking_code}");
        }

        // Straight to the Department Head — re-checked server-side (defense in
        // depth; the encode form only shows this option when it's actually allowed).
        if ($scope === 'head') {
            $actor = $request->user();
            $head = Document::departmentHeadFor($actor->department_id);
            $allowed = (bool) optional($actor->department)->forward_to_head_enabled && $head && $head->id !== $actor->id;
            if (! $allowed) {
                return back()->withInput()->withErrors(['broadcast_scope' => 'Forwarding to the Department Head isn\'t available for your office.']);
            }
            $document = $service->encode($data, $actor, null);
            $service->forwardToHead($document, $actor, $data['assign_remarks'] ?? null);
            \App\Models\ActivityLog::record('documents.store', "Encoded a new document: {$document->title} ({$document->tracking_code}, #{$document->id})", $document);

            return redirect()->route('documents.show', $document)
                ->with('success', "Document encoded and forwarded to the Department Head. Tracking code: {$document->tracking_code}");
        }

        // Transfer to another office's receiving pool (no specific person).
        if ($scope === 'transfer') {
            // The encoder's own office is implicitly the origin for a transfer.
            $actor = $request->user();
            $data['source'] = trim(($actor->department?->code ?? '').($actor->division ? ' · '.$actor->division->name : '')) ?: 'Internal';
            $document = $service->encode($data, $request->user(), null);
            $service->transferToOffice($document, (int) $data['to_department_id'], $request->user(), $data['assign_remarks'] ?: 'Transferred to your office.');
            \App\Models\ActivityLog::record('documents.store', "Encoded a new document: {$document->title} ({$document->tracking_code}, #{$document->id})", $document);

            return redirect()->route('documents.show', $document)
                ->with('success', "Document encoded and sent to the selected office's receiving pool. Tracking code: {$document->tracking_code}");
        }

        // Normal: assign to a specific staff in my own office (or assign later).
        $document = $service->encode($data, $request->user(), $data['assignee_id'] ?? null);
        \App\Models\ActivityLog::record('documents.store', "Encoded a new document: {$document->title} ({$document->tracking_code}, #{$document->id})", $document);

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
            'relatedDocuments.currentHolder',
            'attachments.uploader',
        ]);

        $user = Auth::user();

        return view('documents.show', [
            'document' => $document,
            'users' => $this->assignableUsers(),
            'trackUrl' => route('track.show', $document->tracking_code),
            'crossDept' => \App\Models\Setting::get('allow_cross_department', '0') === '1',
            'departments' => \App\Models\Department::orderByRaw('id = ? desc', [$user->department_id ?? 0])->orderBy('name')->get(),
            'ownDivisions' => Division::where('is_active', true)
                ->when($user->department_id, fn ($q) => $q->where('department_id', $user->department_id))
                ->orderBy('name')->get(),
        ]);
    }

    public function edit(Document $document)
    {
        $this->authorize('update', $document);

        return view('documents.edit', [
            'document' => $document,
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'users' => $this->assignableUsers(),
            'deadlineTypeNames' => \App\Models\DocumentType::where('requires_deadline', true)->pluck('name')->values(),
            'transmittalTypeNames' => \App\Models\DocumentType::where('allows_transmittal', true)->get()
                ->filter(fn ($t) => $t->transmittalAllowedFor(Auth::user()->department_id ?? $document->department_id))
                ->pluck('name')->values(),
            'officeDeadline' => (bool) optional($document->department)->deadline_enabled,
            'officeCalendarDaysGate' => optional($document->department)->time_tracking_mode === 'calendar_days',
            'officeCalendarDaysDefaultWeekends' => (bool) (optional($document->department)->calendar_days_include_weekends ?? true),
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:100'],
            'voucher_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'deadline' => ['nullable', 'date'],
            'is_transmittal' => ['nullable', 'boolean'],
            'transmittal_quantity' => ['nullable', 'required_if:is_transmittal,1', 'integer', 'min:1', 'max:9999'],
            'time_tracking_mode' => ['nullable', 'in:working_hours,calendar_days'],
            'calendar_days_include_weekends' => ['nullable', 'boolean'],
            'division_id' => ['nullable', 'exists:divisions,id'],
        ]);

        if (! Document::priorityEnabled()) {
            unset($data['priority']);
        }

        $type = \App\Models\DocumentType::where('name', $data['document_type'])->first();
        $data['is_transmittal'] = $request->boolean('is_transmittal') && $type?->transmittalAllowedFor($request->user()->department_id);
        if (! $data['is_transmittal']) {
            $data['transmittal_quantity'] = null;
        }

        // Per-document calendar-days override — only honored when this document's
        // own office actually has the feature gated on.
        if (optional($document->department)->time_tracking_mode === 'calendar_days') {
            $data['time_tracking_mode'] = ($data['time_tracking_mode'] ?? null) === 'calendar_days' ? 'calendar_days' : 'working_hours';
            $data['calendar_days_include_weekends'] = $data['time_tracking_mode'] === 'calendar_days'
                ? $request->boolean('calendar_days_include_weekends')
                : null;
        } else {
            unset($data['time_tracking_mode'], $data['calendar_days_include_weekends']);
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

        if ($missing = $this->missingChecklist($document, $request)) {
            return back()->with('error', $missing);
        }

        $service->release($document, $request->user(), $data['remarks'] ?? null);

        return back()->with('success', 'Document released. You can now print the QR code.');
    }

    public function receive(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('receive', $document);

        $data = $request->validate(['remarks' => ['nullable', 'string']]);

        // When attachments exist, the recipient must confirm every item is physically present.
        if ($missing = $this->missingChecklist($document, $request)) {
            return back()->with('error', $missing);
        }

        $service->receive($document, $request->user(), $data['remarks'] ?? null);

        return back()->with('success', 'Document received.');
    }

    public function reject(Request $request, Document $document, DocumentService $service)
    {
        // The intended recipient (same gate as receive) may reject instead of accepting.
        $this->authorize('receive', $document);

        $data = $request->validate(['remarks' => ['required', 'string', 'min:3']]);
        $service->reject($document, $request->user(), $data['remarks']);

        return back()->with('success', 'Document rejected and returned to the sender.');
    }

    /**
     * If the document has attachments, ensure the submitted checklist confirms the
     * main document + every attachment. Returns an error string, or null if OK.
     */
    private function missingChecklist(Document $document, Request $request): ?string
    {
        if (! Document::attachmentsEnabled() || $document->supportingDocuments->isEmpty()) {
            return null;
        }
        // A rejected document being received back by the sender skips the checklist —
        // they knowingly accept it incomplete to sort out the missing item internally.
        if (optional($document->logs()->latest('id')->first())->action === 'rejected') {
            return null;
        }
        $required = $document->supportingDocuments->pluck('id')->map(fn ($id) => 'att_'.$id)->push('main')->all();
        $present = (array) $request->input('present', []);
        if (array_diff($required, $present)) {
            return 'Please tick the main document and every attachment you physically have — or reject if something is missing.';
        }

        return null;
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

        if ($missing = $this->missingChecklist($document, $request)) {
            return back()->with('error', $missing);
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

    public function forwardToHead(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('forwardToHead', $document);

        $data = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        if ($missing = $this->missingChecklist($document, $request)) {
            return back()->with('error', $missing);
        }

        $service->forwardToHead($document, $request->user(), $data['remarks'] ?? null);

        return back()->with('success', 'Document forwarded to the Department Head.');
    }

    public function claimFromHead(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('claimFromHead', $document);

        $service->claimFromHead($document, $request->user());

        return back()->with('success', 'Document claimed — it now shows as with you.');
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

        if ($missing = $this->missingChecklist($document, $request)) {
            return back()->with('error', $missing);
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

    public function distribute(Request $request, Document $document, DocumentService $service)
    {
        $this->authorize('distribute', $document);

        $data = $request->validate([
            'scope' => ['required', 'in:selected,division,department'],
            'recipient_ids' => ['nullable', 'required_if:scope,selected', 'array'],
            'recipient_ids.*' => ['integer', 'exists:users,id'],
            'division_id' => ['nullable', 'required_if:scope,division', 'exists:divisions,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $service->distribute(
            $document,
            $request->user(),
            $data['scope'],
            $data['recipient_ids'] ?? [],
            $data['division_id'] ?? null,
            $data['remarks'] ?? null,
        );

        return back()->with('success', 'Document distributed for acknowledgement.');
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

    /* -------------------- Related documents -------------------- */

    public function linkDocument(Request $request, Document $document)
    {
        abort_unless(Document::linkingEnabled(), 404);
        $this->authorize('view', $document);

        $data = $request->validate(['tracking_code' => ['required', 'string', 'max:100']]);
        $target = Document::where('tracking_code', trim($data['tracking_code']))->first();

        if (! $target) {
            return back()->with('error', 'No document found with that tracking code.');
        }
        if ($target->id === $document->id) {
            return back()->with('error', 'A document cannot be linked to itself.');
        }
        // Relationship guard: you can only link documents you have access to
        // (your own office, or ones that already concern you).
        if ($request->user()->cannot('view', $target)) {
            return back()->with('error', 'You can only link documents you have access to (your office, or ones that concern you).');
        }

        $document->relatedDocuments()->syncWithoutDetaching([$target->id]);
        $target->relatedDocuments()->syncWithoutDetaching([$document->id]);

        return back()->with('success', "Linked to {$target->tracking_code}.");
    }

    public function unlinkDocument(Request $request, Document $document, Document $related)
    {
        $this->authorize('view', $document);

        $document->relatedDocuments()->detach($related->id);
        $related->relatedDocuments()->detach($document->id);

        return back()->with('success', 'Link removed.');
    }

    /* -------------------- Batch receive -------------------- */

    /**
     * A page for receiving many documents at once — handy when a desk gets a stack
     * of QR-tagged documents from several offices. Scan each (or tick it), then
     * receive them all in one go.
     */
    public function batchReceive(Request $request)
    {
        abort_unless(Document::batchReceiveEnabled(), 404);
        $user = $request->user();
        abort_unless($user->can('documents.receive'), 403);

        // Documents released/forwarded directly to me, awaiting my receipt.
        $direct = Document::with(['creator', 'department'])
            ->where('current_holder_id', $user->id)
            ->whereIn('status', ['released', 'forwarded'])
            ->where('is_pending', false)
            ->latest('updated_at')->get()
            ->each->setAttribute('receive_kind', 'receive');

        // Unclaimed transfers in my office (if I'm allowed to claim).
        $pool = collect();
        if ($user->can('documents.claim') && $user->department_id) {
            $pool = Document::with(['creator', 'department'])
                ->whereNull('current_holder_id')
                ->where('status', 'released')
                ->where('is_broadcast', false)
                ->where('department_id', $user->department_id)
                ->latest('updated_at')->get()
                ->each->setAttribute('receive_kind', 'claim');
        }

        return view('documents.batch-receive', ['docs' => $direct->concat($pool)]);
    }

    public function batchReceiveStore(Request $request, DocumentService $service)
    {
        abort_unless(Document::batchReceiveEnabled(), 404);
        abort_unless($request->user()->can('documents.receive'), 403);

        $data = $request->validate([
            'document_ids' => ['required', 'array'],
            'document_ids.*' => ['integer', 'exists:documents,id'],
        ]);

        $received = 0;
        $skipped = 0;
        foreach (Document::whereIn('id', $data['document_ids'])->get() as $doc) {
            if ($request->user()->can('receive', $doc)) {
                $service->receive($doc, $request->user());
                $received++;
            } else {
                $skipped++;
            }
        }

        return redirect()->route('documents.batchReceive')
            ->with('success', "Received {$received} document(s).".($skipped ? " {$skipped} could not be received (no longer yours)." : ''));
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
