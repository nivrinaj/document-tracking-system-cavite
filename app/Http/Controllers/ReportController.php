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

    public function index()
    {
        $user = Auth::user();

        // Quick numbers — scoped to what this user is allowed to see.
        $openBase = fn () => Document::visibleTo($user)->whereIn('status', ['draft', 'released', 'received', 'forwarded']);
        $quick = [
            'total'     => Document::visibleTo($user)->count(),
            'active'    => (clone $openBase())->where('is_pending', false)->count(),
            'pending'   => Document::visibleTo($user)->where('is_pending', true)->count(),
            'completed' => Document::visibleTo($user)->whereIn('status', ['archived', 'completed'])->count(),
        ];

        return view('reports.index', [
            'types' => $this->availableTypes($user),
            'divisions' => $this->visibleDivisions($user),
            'quick' => $quick,
        ]);
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
