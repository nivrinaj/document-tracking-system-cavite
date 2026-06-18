<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isHead = $user->isHead();

        // Base query: heads see the whole department, everyone else only what concerns them.
        $base = Document::query();
        if (! $isHead) {
            $base->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('current_holder_id', $user->id)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id));
            });
        }

        // Cards / counters
        $stats = [
            'total'     => (clone $base)->count(),
            'pending'   => (clone $base)->whereIn('status', ['draft', 'released', 'received', 'forwarded'])->count(),
            'completed' => (clone $base)->whereIn('status', ['archived', 'completed'])->count(),
            'urgent'    => (clone $base)->where('priority', 'urgent')->whereNotIn('status', ['archived', 'completed'])->count(),
        ];

        // My action lists
        $toReceive = Document::with(['creator', 'division'])
            ->where('current_holder_id', $user->id)
            ->whereIn('status', ['released', 'forwarded'])
            ->latest('updated_at')->take(8)->get();

        $toAction = Document::with(['creator', 'division'])
            ->where('current_holder_id', $user->id)
            ->where('status', 'received')
            ->latest('updated_at')->take(8)->get();

        $toRelease = collect();
        if ($user->can('documents.release')) {
            $toRelease = Document::with(['currentHolder', 'division'])
                ->where('created_by', $user->id)
                ->where('status', 'draft')
                ->whereNotNull('current_holder_id')
                ->latest('updated_at')->take(8)->get();
        }

        // Recent activity (scoped)
        $activityQuery = DocumentLog::with(['document', 'actor', 'toUser'])->latest();
        if (! $isHead) {
            $activityQuery->whereHas('document', function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('current_holder_id', $user->id)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id));
            });
        }
        $activity = $activityQuery->take(10)->get();

        // Status breakdown for a small chart
        $statusBreakdown = (clone $base)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status')->toArray();

        // Priority breakdown (for a doughnut chart)
        $priorityBreakdown = (clone $base)
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')->pluck('total', 'priority')->toArray();

        // Incoming trend: documents encoded per day for the last 14 days.
        $trend = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $trend[] = [
                'label' => $day->format('M d'),
                'count' => (clone $base)->whereDate('created_at', $day->toDateString())->count(),
            ];
        }

        return view('dashboard', compact(
            'stats', 'toReceive', 'toAction', 'toRelease', 'activity',
            'statusBreakdown', 'priorityBreakdown', 'trend', 'isHead'
        ));
    }
}
