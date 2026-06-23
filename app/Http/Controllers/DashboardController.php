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
        $isHead = $user->can('documents.viewAll') || $user->isHead();

        // Base query scoped to what this user is allowed to see (department / all / concerns).
        $base = Document::query()->visibleTo($user);

        // Cards / counters — split by life-cycle stage so a not-yet-released
        // draft is NOT mistaken for something pending on a recipient.
        $stats = [
            // Encoded but not released yet — the receiving staff still has to release it.
            'awaiting_release' => (clone $base)->where('status', 'draft')->count(),
            // Released/forwarded and on its way — waiting for the recipient to receive.
            'in_transit' => (clone $base)->whereIn('status', ['released', 'forwarded'])->count(),
            // Received and actively being worked on.
            'active' => (clone $base)->where('status', 'received')->count(),
            // Finished.
            'completed' => (clone $base)->whereIn('status', ['archived', 'completed'])->count(),
        ];

        // My action lists
        $toReceive = Document::with(['creator', 'division'])
            ->where('current_holder_id', $user->id)
            ->whereIn('status', ['released', 'forwarded'])
            ->latest('updated_at')->take(8)->get();

        // Unclaimed transfers sitting in the user's office (any receiver can claim).
        $toClaim = collect();
        if ($user->can('documents.claim') && $user->department_id) {
            $toClaim = Document::with('creator')
                ->whereNull('current_holder_id')
                ->where('status', 'released')
                ->where('is_broadcast', false)
                ->where('department_id', $user->department_id)
                ->latest('updated_at')->take(8)->get();
        }

        $toAction = Document::with(['creator', 'division'])
            ->where('current_holder_id', $user->id)
            ->where('status', 'received')
            ->latest('updated_at')->take(8)->get();

        // Documents distributed to me for acknowledgement that I haven't acknowledged yet.
        $toAcknowledge = Document::with('creator')
            ->where('is_broadcast', true)
            ->whereNotIn('status', ['archived', 'completed'])
            ->whereHas('assignees', fn ($a) => $a->where('users.id', $user->id)->whereNull('document_assignees.acknowledged_at'))
            ->latest('updated_at')->take(8)->get();

        $toRelease = collect();
        if ($user->can('documents.release')) {
            $toRelease = Document::with(['currentHolder', 'division'])
                ->where('created_by', $user->id)
                ->where('status', 'draft')
                ->whereNotNull('current_holder_id')
                ->latest('updated_at')->take(8)->get();
        }

        // Recent activity (scoped to documents the user can see)
        $activity = DocumentLog::with(['document', 'actor', 'toUser'])->latest()
            ->whereHas('document', fn ($q) => $q->visibleTo($user))
            ->take(10)->get();

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
            'stats', 'toReceive', 'toClaim', 'toAction', 'toRelease', 'toAcknowledge', 'activity',
            'statusBreakdown', 'priorityBreakdown', 'trend', 'isHead'
        ));
    }
}
