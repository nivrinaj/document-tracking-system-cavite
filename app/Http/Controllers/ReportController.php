<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Document;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** All report types. The Processing Time report is only offered to offices that have one. */
    public const TYPES = [
        'summary'        => 'Summary Overview (counts by status, division & priority)',
        'aging'          => 'Aging & Bottlenecks (oldest documents & where they are stuck)',
        'incoming'       => 'Incoming Documents (encoded within a date range)',
        'by_status'      => 'Documents by Status',
        'by_division'    => 'Documents by Division',
        'staff_workload' => 'Staff Workload (documents currently held)',
        'pending'        => 'Pending / Outstanding Documents',
        'completed'      => 'Completed & Archived Documents',
        'sla_compliance' => 'Processing Time & Overdue (on-time vs late)',
    ];

    /**
     * Report types this user may run. The Processing Time / Overdue report is
     * only relevant to offices that actually have a processing time configured
     * (e.g. Accounting), or to users who can see every department.
     */
    private function availableTypes(User $user): array
    {
        $types = self::TYPES;

        $hasProcessingTime = $user->canViewAllDepartments()
            || ($user->department_id && \App\Models\Department::where('id', $user->department_id)
                ->where('sla_enabled', true)->whereNotNull('sla_days')->exists());

        if (! $hasProcessingTime) {
            unset($types['sla_compliance']);
        }

        return $types;
    }

    /** Divisions this user may filter by — their own office only, unless they can view all. */
    private function visibleDivisions(User $user)
    {
        if ($user->canViewAllDepartments()) {
            return Division::orderBy('name')->get();
        }

        return Division::where('department_id', $user->department_id)->orderBy('name')->get();
    }

    /* ═══════════════ Transmittal of Reviewed Disbursement ═══════════════ */

    public const TRANSMITTAL_COLS = [
        'date_jev' => 'Date Received JEV', 'dv' => 'DV No.', 'obr' => 'OBR No.', 'rc' => 'RC',
        'fund' => 'Fund', 'payee' => 'Payee', 'nature' => 'Nature', 'particulars' => 'Particulars / Explanation',
        'amount' => 'Amount', 'date_review' => 'Date Received Review',
        'secretary' => 'Received by Secretary', 'releasing' => 'Received by Releasing Staff',
        'days' => 'No. of Days', 'date_in' => 'Date In', 'date_out' => 'Date Out',
    ];

    private const TRANSMITTAL_ALIGN_DEFAULT = [
        'date_jev' => 'center', 'dv' => 'center', 'obr' => 'left', 'rc' => 'left',
        'fund' => 'center', 'payee' => 'left', 'nature' => 'center', 'particulars' => 'left',
        'amount' => 'right', 'date_review' => 'center',
        'secretary' => 'center', 'releasing' => 'center', 'days' => 'center',
        'date_in' => 'center', 'date_out' => 'center',
    ];

    private function transmittalAlign(): array
    {
        $saved = json_decode((string) Setting::get('transmittal_align', ''), true) ?: [];
        return array_merge(self::TRANSMITTAL_ALIGN_DEFAULT, array_intersect_key($saved, self::TRANSMITTAL_ALIGN_DEFAULT));
    }

    private function transmittalLabels(): array
    {
        $saved = json_decode((string) Setting::get('transmittal_labels', ''), true) ?: [];
        return array_merge(self::TRANSMITTAL_COLS, array_intersect_key(array_filter($saved), self::TRANSMITTAL_COLS));
    }

    private function canRunTransmittal(User $user): bool
    {
        if ($user->canViewAllDepartments()) return true;

        $offices = array_filter(explode(',', (string) Setting::get('transmittal_offices', '')));
        if (empty($offices) || ! in_array((string) $user->department_id, $offices, true)) return false;

        $divisions = array_filter(explode(',', (string) Setting::get('transmittal_divisions', '')));
        if (empty($divisions)) return true;

        $isHead = $user->hasRole(['Department Head', 'Assistant Department Head']);
        return $isHead || in_array((string) $user->division_id, $divisions, true);
    }

    /* ═══════════════ E-Record ═══════════════ */

    /** E-Record columns and their default alignment (overridable in Report Settings). */
    public const ERECORD_COLS = [
        'date' => 'Date Received', 'dv' => 'DV #', 'obr' => 'OBR No.', 'rc' => 'RC',
        'fund' => 'Fund', 'payee' => 'Payee', 'nature' => 'Nature', 'particulars' => 'Particulars', 'amount' => 'Amount',
    ];

    private const ERECORD_ALIGN_DEFAULT = [
        'date' => 'center', 'dv' => 'center', 'obr' => 'left', 'rc' => 'left', 'fund' => 'center',
        'payee' => 'left', 'nature' => 'center', 'particulars' => 'left', 'amount' => 'right',
    ];

    private function erecordAlign(): array
    {
        $saved = json_decode((string) Setting::get('erecord_align', ''), true) ?: [];

        return array_merge(self::ERECORD_ALIGN_DEFAULT, array_intersect_key($saved, self::ERECORD_ALIGN_DEFAULT));
    }

    private function erecordLabels(): array
    {
        $saved = json_decode((string) Setting::get('erecord_labels', ''), true) ?: [];

        return array_merge(self::ERECORD_COLS, array_intersect_key(array_filter($saved), self::ERECORD_COLS));
    }

    /** Offices allowed to run the E-Record report — Super Admin, the configured offices, else accounting offices. */
    private function canRunERecord(User $user): bool
    {
        if ($user->canViewAllDepartments()) {
            return true;
        }
        $offices = array_values(array_filter(explode(',', (string) Setting::get('erecord_offices', ''))));
        if (! empty($offices)) {
            return in_array((string) $user->department_id, $offices, true);
        }

        return (bool) optional($user->department)->is_accounting;
    }

    public function index()
    {
        $user = Auth::user();

        // Only list reports relevant to this user's office.
        $reports = [];
        if ($this->canRunERecord($user)) {
            $reports['erecord'] = 'E-Record';
        }
        if ($this->canRunTransmittal($user)) {
            $reports['transmittal'] = Setting::get('transmittal_title', 'Transmittal of Reviewed Disbursement');
        }

        $funds = \App\Models\Fund::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('reports.index', [
            'reports' => $reports,
            'eDocTypes' => \App\Models\DocumentType::availableFor($user->department_id)->pluck('name'),
            'eFunds' => $funds,
            'tFunds' => $funds,
            'tDateSource' => Setting::get('transmittal_date_source', 'received_by_division'),
        ]);
    }

    /**
     * E-Record — encoded Vouchers/Payroll for a chosen Document Type + Fund within a
     * date/time range, regardless of status. Title & paper come from report settings.
     */
    public function erecord(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->canRunERecord($user), 403, 'This report is for accounting offices.');

        $data = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'fund_id' => ['required', 'exists:funds,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'hospital' => ['nullable', 'in:exclude,include,only'],
            'format' => ['nullable', 'in:html,pdf'],
        ]);

        $from = $request->filled('date_from') ? Carbon::parse($data['date_from']) : null;
        $to = $request->filled('date_to') ? Carbon::parse($data['date_to']) : null;
        if ($from && strlen($data['date_from']) <= 10) $from = $from->startOfDay();
        if ($to && strlen($data['date_to']) <= 10) $to = $to->endOfDay();
        $deptId = $user->canViewAllDepartments() ? null : $user->department_id;
        $hospital = $data['hospital'] ?? 'exclude';

        // Hospital transactions are flagged on the document at encode time (is_hospital).
        $rows = Document::query()
            ->with(['fund', 'responsibilityCenter'])
            ->where('document_type', $data['document_type'])
            ->where('fund_id', $data['fund_id'])
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($hospital === 'only', fn ($q) => $q->where('is_hospital', true))
            ->when($hospital === 'exclude', fn ($q) => $q->where('is_hospital', false))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(tracking_code, '-', 4), '-', -1) AS UNSIGNED)")
            ->get();

        $fund = \App\Models\Fund::find($data['fund_id']);
        $orientation = Setting::get('erecord_orientation', 'landscape');

        $payload = [
            'reportTitle' => Setting::get('erecord_title', 'E-Record'),
            'officeName' => optional($user->department)->name,
            'rows' => $rows,
            'fund' => $fund,
            'documentType' => $data['document_type'],
            'from' => $from,
            'to' => $to,
            'hospital' => $hospital,
            'align' => $this->erecordAlign(),
            'colLabels' => $this->erecordLabels(),
            'perPage' => $orientation === 'portrait' ? 26 : 16,
            'natureCodes' => \App\Models\NatureOfTransaction::pluck('report_code', 'name'),
            'org' => Setting::get('organization', ''),
            'appName' => Setting::get('app_name', config('app.name')),
            'generatedAt' => now(),
            'total' => $rows->sum('amount'),
        ];

        if ($request->input('format') === 'html') {
            return view('reports.erecord', $payload + ['preview' => true]);
        }

        return Pdf::loadView('reports.erecord', $payload)
            ->setPaper(Setting::get('erecord_paper', 'a4'), $orientation)
            ->setOption('isPhpEnabled', true)
            ->stream('E-Record-'.$fund->reportCode().($hospital === 'only' ? '-H' : '').'-'.now()->format('Ymd-His').'.pdf');
    }

    /* ───────────── Transmittal of Reviewed Disbursement ───────────── */

    public function transmittal(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->canRunTransmittal($user), 403);

        $data = $request->validate([
            'fund_id' => ['required', 'exists:funds,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'hospital' => ['nullable', 'in:exclude,include,only'],
            'date_source' => ['nullable', 'in:received_by_division,created'],
            'format' => ['nullable', 'in:html,pdf'],
        ]);

        $from = $request->filled('date_from') ? Carbon::parse($data['date_from'])->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($data['date_to'])->endOfDay() : null;
        $hospital = $data['hospital'] ?? 'exclude';
        $dateSource = $data['date_source'] ?? Setting::get('transmittal_date_source', 'received_by_division');

        $divisionIds = array_filter(explode(',', (string) Setting::get('transmittal_divisions', '')));
        $deptId = $user->canViewAllDepartments() ? null : $user->department_id;

        $rows = Document::query()
            ->with(['fund', 'responsibilityCenter'])
            ->where('fund_id', $data['fund_id'])
            ->whereIn('document_type', ['Voucher', 'Payroll'])
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($hospital === 'only', fn ($q) => $q->where('is_hospital', true))
            ->when($hospital === 'exclude', fn ($q) => $q->where('is_hospital', false))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(tracking_code, '-', 4), '-', -1) AS UNSIGNED)")
            ->get();

        // Resolve the "date received" per document.
        if ($dateSource === 'received_by_division' && ! empty($divisionIds)) {
            $docIds = $rows->pluck('id');
            $userIdsInDiv = User::whereIn('division_id', $divisionIds)->pluck('id');
            $receivedDates = \App\Models\DocumentLog::whereIn('document_id', $docIds)
                ->where('action', 'received')
                ->whereIn('actor_id', $userIdsInDiv)
                ->orderBy('created_at')
                ->get()
                ->groupBy('document_id')
                ->map(fn ($logs) => $logs->first()->created_at);
        } else {
            $receivedDates = collect();
        }

        $fund = \App\Models\Fund::find($data['fund_id']);

        $payload = [
            'reportTitle' => Setting::get('transmittal_title', 'Transmittal of Reviewed Disbursement'),
            'isoCode' => Setting::get('transmittal_iso', 'PGC ACCTG. R.002'),
            'officeName' => optional($user->department)->name,
            'divisionName' => optional($user->division)->name,
            'rows' => $rows,
            'fund' => $fund,
            'from' => $from,
            'to' => $to,
            'hospital' => $hospital,
            'dateSource' => $dateSource,
            'receivedDates' => $receivedDates,
            'align' => $this->transmittalAlign(),
            'colLabels' => $this->transmittalLabels(),
            'perPage' => 16,
            'natureCodes' => \App\Models\NatureOfTransaction::pluck('report_code', 'name'),
            'org' => Setting::get('organization', ''),
            'total' => $rows->sum('amount'),
            'showPageNumber' => (bool) Setting::get('transmittal_page_number', true),
            'showTotals' => (bool) Setting::get('transmittal_show_totals', true),
        ];

        if ($request->input('format') === 'html') {
            return view('reports.transmittal', $payload + ['preview' => true]);
        }

        $hospSuffix = $hospital === 'only' ? '-H' : '';
        return Pdf::loadView('reports.transmittal', $payload)
            ->setPaper('a4', 'landscape')
            ->setOption('isPhpEnabled', true)
            ->stream('Transmittal-'.$fund->reportCode().$hospSuffix.'-'.now()->format('Ymd-His').'.pdf');
    }

    /* ───────────── Report settings (Super Admin) ───────────── */
    public function settings()
    {
        $departments = \App\Models\Department::orderBy('name')->get();
        $divisions = Division::orderBy('name')->get();

        return view('reports.settings', [
            'departments' => $departments,
            'divisions' => $divisions,
            // E-Record
            'title' => Setting::get('erecord_title', 'E-Record'),
            'paper' => Setting::get('erecord_paper', 'a4'),
            'orientation' => Setting::get('erecord_orientation', 'landscape'),
            'offices' => array_values(array_filter(explode(',', (string) Setting::get('erecord_offices', '')))),
            'cols' => self::ERECORD_COLS,
            'align' => $this->erecordAlign(),
            'labels' => $this->erecordLabels(),
            // Transmittal
            'tTitle' => Setting::get('transmittal_title', 'Transmittal of Reviewed Disbursement'),
            'tIso' => Setting::get('transmittal_iso', 'PGC ACCTG. R.002'),
            'tOffices' => array_values(array_filter(explode(',', (string) Setting::get('transmittal_offices', '')))),
            'tDivisions' => array_values(array_filter(explode(',', (string) Setting::get('transmittal_divisions', '')))),
            'tDateSource' => Setting::get('transmittal_date_source', 'received_by_division'),
            'tPageNumber' => (bool) Setting::get('transmittal_page_number', true),
            'tShowTotals' => (bool) Setting::get('transmittal_show_totals', true),
            'tCols' => self::TRANSMITTAL_COLS,
            'tAlign' => $this->transmittalAlign(),
            'tLabels' => $this->transmittalLabels(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $report = $request->input('_report', 'erecord');

        if ($report === 'transmittal') {
            $data = $request->validate([
                'transmittal_title' => ['required', 'string', 'max:150'],
                'transmittal_iso' => ['nullable', 'string', 'max:80'],
                'transmittal_offices' => ['nullable', 'array'],
                'transmittal_offices.*' => ['integer', 'exists:departments,id'],
                'transmittal_divisions' => ['nullable', 'array'],
                'transmittal_divisions.*' => ['integer', 'exists:divisions,id'],
                'transmittal_date_source' => ['required', 'in:received_by_division,created'],
                'transmittal_page_number' => ['nullable'],
                'transmittal_show_totals' => ['nullable'],
                'align' => ['nullable', 'array'],
                'align.*' => ['in:left,center,right'],
                'labels' => ['nullable', 'array'],
                'labels.*' => ['nullable', 'string', 'max:50'],
            ]);
            Setting::put('transmittal_title', $data['transmittal_title']);
            Setting::put('transmittal_iso', $data['transmittal_iso'] ?? '');
            Setting::put('transmittal_offices', implode(',', $data['transmittal_offices'] ?? []));
            Setting::put('transmittal_divisions', implode(',', $data['transmittal_divisions'] ?? []));
            Setting::put('transmittal_date_source', $data['transmittal_date_source']);
            Setting::put('transmittal_page_number', $request->boolean('transmittal_page_number') ? '1' : '0');
            Setting::put('transmittal_show_totals', $request->boolean('transmittal_show_totals') ? '1' : '0');
            Setting::put('transmittal_align', json_encode(array_intersect_key($data['align'] ?? [], self::TRANSMITTAL_COLS)));
            Setting::put('transmittal_labels', json_encode(array_intersect_key($data['labels'] ?? [], self::TRANSMITTAL_COLS)));

            return back()->with('success', 'Transmittal settings saved.');
        }

        $data = $request->validate([
            'erecord_title' => ['required', 'string', 'max:150'],
            'erecord_paper' => ['required', 'in:a4,letter,legal'],
            'erecord_orientation' => ['required', 'in:landscape,portrait'],
            'erecord_offices' => ['nullable', 'array'],
            'erecord_offices.*' => ['integer', 'exists:departments,id'],
            'align' => ['nullable', 'array'],
            'align.*' => ['in:left,center,right'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:50'],
        ]);
        Setting::put('erecord_title', $data['erecord_title']);
        Setting::put('erecord_paper', $data['erecord_paper']);
        Setting::put('erecord_orientation', $data['erecord_orientation']);
        Setting::put('erecord_offices', implode(',', $data['erecord_offices'] ?? []));
        Setting::put('erecord_align', json_encode(array_intersect_key($data['align'] ?? [], self::ERECORD_COLS)));
        Setting::put('erecord_labels', json_encode(array_intersect_key($data['labels'] ?? [], self::ERECORD_COLS)));

        return back()->with('success', 'Report settings saved.');
    }

    public function generate(Request $request)
    {
        $user = Auth::user();
        $allowedTypes = $this->availableTypes($user);

        $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys($allowedTypes))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'format' => ['nullable', 'in:html,pdf'],
        ]);

        $type = $request->input('type');
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : null;

        // A user may only filter by a division inside their own office (unless they see all).
        $divisionId = $request->input('division_id');
        if ($divisionId && ! $this->visibleDivisions($user)->contains('id', (int) $divisionId)) {
            $divisionId = null;
        }

        $payload = $this->build($type, $from, $to, $divisionId, $user);

        $data = array_merge($payload, [
            'reportTitle' => $allowedTypes[$type],
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'division' => $divisionId ? Division::find($divisionId) : null,
            'generatedAt' => now(),
            'org' => Setting::get('organization', ''),
            'appName' => Setting::get('app_name', config('app.name')),
        ]);

        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'portrait');

            return $pdf->download('report-'.$type.'-'.now()->format('Ymd-His').'.pdf');
        }

        return view('reports.result', $data);
    }

    /** Build the dataset for a given report type. Always scoped to what $user may see. */
    private function build(string $type, ?Carbon $from, ?Carbon $to, $divisionId, User $user): array
    {
        // Base query is ALWAYS limited to documents this user is allowed to see.
        $base = fn () => Document::query()->visibleTo($user);

        $scope = function ($q) use ($from, $to, $divisionId) {
            if ($from) {
                $q->where('created_at', '>=', $from);
            }
            if ($to) {
                $q->where('created_at', '<=', $to);
            }
            if ($divisionId) {
                $q->where('division_id', $divisionId);
            }

            return $q;
        };

        return match ($type) {
            'summary' => array_merge([
                'byStatus' => $scope($base())->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status')->toArray(),
                'byPriority' => Document::priorityEnabled()
                    ? $scope($base())->select('priority', DB::raw('count(*) as total'))->groupBy('priority')->pluck('total', 'priority')->toArray()
                    : [],
                'byDivision' => $scope($base())->with('division')->select('division_id', DB::raw('count(*) as total'))->groupBy('division_id')->get()
                    ->mapWithKeys(fn ($r) => [optional($r->division)->code ?? 'Unassigned' => $r->total])->toArray(),
                'pendingCount' => $scope($base())->where('is_pending', true)->count(),
                'documents' => collect(),
            ], ['stats' => $this->completionStats($scope($base()))]),
            'aging' => $this->buildAging($scope($base()->with(['creator', 'currentHolder.department', 'currentHolder.division', 'department', 'openPossession.holder.department', 'openPossession.holder.division', 'openPossession.department']))),
            'incoming', 'pending', 'completed', 'by_status', 'by_division' => [
                'documents' => $scope($base()->with(['creator', 'currentHolder', 'division']))
                    ->when($type === 'pending', fn ($q) => $q->whereIn('status', ['draft', 'released', 'received', 'forwarded']))
                    ->when($type === 'completed', fn ($q) => $q->whereIn('status', ['archived', 'completed']))
                    ->when($type === 'by_status', fn ($q) => $q->orderBy('status'))
                    ->when($type === 'by_division', fn ($q) => $q->orderBy('division_id'))
                    ->latest()->get(),
            ],
            'staff_workload' => [
                'documents' => collect(),
                'workload' => $scope($base())
                    ->whereNotNull('current_holder_id')
                    ->whereNotIn('status', ['archived', 'completed'])
                    ->with('currentHolder.division', 'currentHolder.department')
                    ->select('current_holder_id', DB::raw('count(*) as total'))
                    ->groupBy('current_holder_id')
                    ->orderByDesc('total')->get(),
            ],
            'sla_compliance' => $this->buildSla($from, $to, $divisionId, $user),
            default => ['documents' => collect()],
        };
    }

    /**
     * Aging / bottlenecks — every OPEN document (excluding those marked pending)
     * ordered oldest-first, so you can see what is taking too long and who is
     * holding it up. Includes total lifetime and time with the current holder.
     */
    private function buildAging($q): array
    {
        $docs = $q->whereNotIn('status', ['archived', 'completed'])
            ->where('is_pending', false)
            ->where('is_broadcast', false)
            ->orderBy('created_at') // oldest first
            ->get();

        $holderSeconds = $docs->map(fn ($d) => $d->secondsWithCurrentHolder())->filter(fn ($v) => $v > 0);

        // How long documents have been sitting with their current holder, bucketed.
        $buckets = ['under_1h' => 0, 'h1_8' => 0, 'h8_24' => 0, 'd1_3' => 0, 'over_3d' => 0];
        foreach ($docs as $d) {
            $s = $d->secondsWithCurrentHolder();
            if ($s < 3600) {
                $buckets['under_1h']++;
            } elseif ($s < 8 * 3600) {
                $buckets['h1_8']++;
            } elseif ($s < 24 * 3600) {
                $buckets['h8_24']++;
            } elseif ($s < 3 * 86400) {
                $buckets['d1_3']++;
            } else {
                $buckets['over_3d']++;
            }
        }

        return [
            'documents' => collect(),
            'aging' => $docs,
            'agingStats' => [
                'count' => $docs->count(),
                'oldest' => $docs->first(),
                'avg_holder' => $holderSeconds->count() ? (int) round($holderSeconds->avg()) : null,
                'longest_holder' => $holderSeconds->count() ? (int) $holderSeconds->max() : null,
                'buckets' => $buckets,
            ],
        ];
    }

    /**
     * Lifecycle statistics for a set of documents — how long things take to finish.
     * $q is a query already scoped/filtered for the report.
     */
    private function completionStats($q): array
    {
        $done = (clone $q)->whereIn('status', ['completed', 'archived'])
            ->whereNotNull('completed_at')->get(['received_at', 'created_at', 'completed_at']);

        $days = $done->map(function ($d) {
            $start = $d->received_at ?? $d->created_at;
            return $start ? $start->floatDiffInDays($d->completed_at) : null;
        })->filter(fn ($v) => $v !== null);

        // Open items still in progress, and how old they are.
        $open = (clone $q)->whereIn('status', ['draft', 'released', 'received', 'forwarded'])->get(['received_at', 'created_at']);
        $openAge = $open->map(fn ($d) => ($d->received_at ?? $d->created_at)?->floatDiffInDays(now()))->filter(fn ($v) => $v !== null);

        return [
            'completed_count' => $done->count(),
            'avg_completion'  => $days->count() ? round($days->avg(), 1) : null,
            'fastest'         => $days->count() ? round($days->min(), 1) : null,
            'slowest'         => $days->count() ? round($days->max(), 1) : null,
            'open_count'      => $open->count(),
            'avg_open_age'    => $openAge->count() ? round($openAge->avg(), 1) : null,
        ];
    }

    /** Evaluate documents against each department's configured processing time. Scoped to $user. */
    private function buildSla(?Carbon $from, ?Carbon $to, $divisionId, User $user): array
    {
        $deptQuery = \App\Models\Department::where('sla_enabled', true)->whereNotNull('sla_days');
        // A user who can't see all departments only ever evaluates their own office.
        if (! $user->canViewAllDepartments()) {
            $deptQuery->where('id', $user->department_id);
        }
        $departments = $deptQuery->get()->keyBy('id');

        $rows = collect();
        $summary = ['on_time' => 0, 'overdue' => 0, 'on_track' => 0, 'overdue_open' => 0];
        $completedDays = [];   // turnaround of finished docs
        $overByDays = [];      // how many days past the limit (late + open-overdue)

        if ($departments->isNotEmpty()) {
            $docs = Document::with(['department', 'currentHolder'])
                ->visibleTo($user)
                ->whereIn('department_id', $departments->keys())
                ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
                ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
                ->latest()->get();

            foreach ($docs as $d) {
                $dept = $departments[$d->department_id];
                $slaTypes = array_map('strtolower', (array) ($dept->sla_document_type ?? []));
                if (! empty($slaTypes) && ! in_array(strtolower($d->document_type), $slaTypes, true)) {
                    continue;
                }
                $start = $d->received_at ?? $d->created_at;
                $sla = (int) $dept->sla_days;

                if ($d->isClosed() && $d->completed_at) {
                    $exact = $start->floatDiffInDays($d->completed_at);
                    $days = (int) round($exact);
                    $status = $days <= $sla ? 'on_time' : 'overdue';
                    $completedDays[] = $exact;
                    if ($status === 'overdue') {
                        $overByDays[] = $exact - $sla;
                    }
                } else {
                    $exact = $start->floatDiffInDays(now());
                    $days = (int) round($exact);
                    $status = $days > $sla ? 'overdue_open' : 'on_track';
                    if ($status === 'overdue_open') {
                        $overByDays[] = $exact - $sla;
                    }
                }
                $summary[$status]++;
                $rows->push([
                    'doc' => $d,
                    'dept' => $dept->code,
                    'sla' => $sla,
                    'days' => $days,
                    'status' => $status,
                ]);
            }
        }

        $slaStats = [
            'avg_completion' => count($completedDays) ? round(array_sum($completedDays) / count($completedDays), 1) : null,
            'avg_over'       => count($overByDays) ? round(array_sum($overByDays) / count($overByDays), 1) : null,
            'worst_over'     => count($overByDays) ? round(max($overByDays), 1) : null,
        ];

        return ['slaRows' => $rows, 'slaSummary' => $summary, 'slaStats' => $slaStats, 'slaDepartments' => $departments->values()];
    }
}
