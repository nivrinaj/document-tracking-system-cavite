<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Document;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Recommended report types shown on the Reports page. */
    public const TYPES = [
        'summary'        => 'Summary Overview (counts by status, division & priority)',
        'incoming'       => 'Incoming Documents (encoded within a date range)',
        'by_status'      => 'Documents by Status',
        'by_division'    => 'Documents by Division',
        'staff_workload' => 'Staff Workload (documents currently held)',
        'pending'        => 'Pending / Outstanding Documents',
        'completed'      => 'Completed & Archived Documents',
        'sla_compliance' => 'Processing Time & Overdue (on-time vs late)',
    ];

    public function index()
    {
        // A few quick numbers for the cards at the top of the page.
        $quick = [
            'total'     => Document::count(),
            'pending'   => Document::whereIn('status', ['draft', 'released', 'received', 'forwarded'])->count(),
            'completed' => Document::whereIn('status', ['archived', 'completed'])->count(),
            'this_month' => Document::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return view('reports.index', [
            'types' => self::TYPES,
            'divisions' => Division::orderBy('name')->get(),
            'quick' => $quick,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'format' => ['nullable', 'in:html,pdf'],
        ]);

        $type = $request->input('type');
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : null;
        $divisionId = $request->input('division_id');

        $payload = $this->build($type, $from, $to, $divisionId);

        $data = array_merge($payload, [
            'reportTitle' => self::TYPES[$type],
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

    /** Build the dataset for a given report type. */
    private function build(string $type, ?Carbon $from, ?Carbon $to, $divisionId): array
    {
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
            'summary' => [
                'byStatus' => $scope(Document::query())->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status')->toArray(),
                'byPriority' => $scope(Document::query())->select('priority', DB::raw('count(*) as total'))->groupBy('priority')->pluck('total', 'priority')->toArray(),
                'byDivision' => $scope(Document::query())->with('division')->select('division_id', DB::raw('count(*) as total'))->groupBy('division_id')->get()
                    ->mapWithKeys(fn ($r) => [optional($r->division)->code ?? 'Unassigned' => $r->total])->toArray(),
                'documents' => collect(),
            ],
            'incoming', 'pending', 'completed', 'by_status', 'by_division' => [
                'documents' => $scope(Document::query()->with(['creator', 'currentHolder', 'division']))
                    ->when($type === 'pending', fn ($q) => $q->whereIn('status', ['draft', 'released', 'received', 'forwarded']))
                    ->when($type === 'completed', fn ($q) => $q->whereIn('status', ['archived', 'completed']))
                    ->when($type === 'by_status', fn ($q) => $q->orderBy('status'))
                    ->when($type === 'by_division', fn ($q) => $q->orderBy('division_id'))
                    ->latest()->get(),
            ],
            'staff_workload' => [
                'documents' => collect(),
                'workload' => $scope(Document::query())
                    ->whereNotNull('current_holder_id')
                    ->whereNotIn('status', ['archived', 'completed'])
                    ->with('currentHolder.division')
                    ->select('current_holder_id', DB::raw('count(*) as total'))
                    ->groupBy('current_holder_id')->get(),
            ],
            'sla_compliance' => $this->buildSla($from, $to, $divisionId),
            default => ['documents' => collect()],
        };
    }

    /** Evaluate documents against each department's configured turnaround SLA. */
    private function buildSla(?Carbon $from, ?Carbon $to, $divisionId): array
    {
        $departments = \App\Models\Department::where('sla_enabled', true)->whereNotNull('sla_days')->get()->keyBy('id');

        $rows = collect();
        $summary = ['on_time' => 0, 'overdue' => 0, 'on_track' => 0, 'overdue_open' => 0];

        if ($departments->isNotEmpty()) {
            $docs = Document::with(['department', 'currentHolder'])
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
                    $days = (int) $start->diffInDays($d->completed_at);
                    $status = $days <= $sla ? 'on_time' : 'overdue';
                } else {
                    $days = (int) $start->diffInDays(now());
                    $status = $days > $sla ? 'overdue_open' : 'on_track';
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

        return ['slaRows' => $rows, 'slaSummary' => $summary, 'slaDepartments' => $departments->values()];
    }
}
