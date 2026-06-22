<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild the possession ledger from each document's history logs so that
 * per-holder timing is accurate for documents that existed before the ledger
 * (including already-completed ones). Mirrors DocumentService's possession rules:
 *   encoded  -> encoder starts holding
 *   received -> the receiver starts holding (previous segment closes)
 *   resumed  -> the holder starts holding again
 *   pending  -> clock pauses (segment closes, none opens)
 *   transferred -> office pool (no person) holds it
 *   archived/completed -> final close
 * forwarded / assigned / released do NOT move possession (holder still has the paper
 * until the next person receives it).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only touch documents that don't yet have a proper ledger (0 or 1 crude row).
        $docIds = DB::table('documents')->pluck('id');

        foreach ($docIds as $docId) {
            $count = DB::table('document_possessions')->where('document_id', $docId)->count();
            if ($count > 1) {
                continue; // already has real, action-built history
            }

            $logs = DB::table('document_logs')
                ->where('document_id', $docId)
                ->orderBy('created_at')->orderBy('id')
                ->get();
            if ($logs->isEmpty()) {
                continue;
            }

            $doc = DB::table('documents')->find($docId);
            $dept = $doc->department_id;

            // wipe the crude backfill and replay
            DB::table('document_possessions')->where('document_id', $docId)->delete();

            $segments = [];
            $open = null; // ['holder'=>?, 'started'=>Carbon]

            $close = function (Carbon $at) use (&$open, &$segments, $dept) {
                if ($open) {
                    $segments[] = [
                        'holder_id' => $open['holder'],
                        'department_id' => $dept,
                        'started_at' => $open['started'],
                        'ended_at' => $at,
                    ];
                    $open = null;
                }
            };

            foreach ($logs as $log) {
                $at = Carbon::parse($log->created_at);
                switch ($log->action) {
                    case 'encoded':
                        $open = ['holder' => $doc->created_by, 'started' => $at];
                        break;
                    case 'received':
                    case 'resumed':
                        $close($at);
                        $open = ['holder' => $log->actor_id, 'started' => $at];
                        break;
                    case 'transferred':
                        $close($at);
                        $open = ['holder' => null, 'started' => $at]; // office pool
                        break;
                    case 'pending':
                        $close($at);
                        break;
                    case 'archived':
                    case 'completed':
                        $close($at);
                        break;
                    // forwarded / assigned / released: no possession change
                }
            }

            // Leave the final segment open if the document is still active.
            $isClosed = in_array($doc->status, ['archived', 'completed']);
            $possessionStart = null;
            if ($open && ! $isClosed && ! $doc->is_pending) {
                $segments[] = [
                    'holder_id' => $open['holder'],
                    'department_id' => $dept,
                    'started_at' => $open['started'],
                    'ended_at' => null,
                ];
                $possessionStart = $open['started'];
            } elseif ($open) {
                // closed/pending but a segment was still notionally open — close it now.
                $segments[] = [
                    'holder_id' => $open['holder'],
                    'department_id' => $dept,
                    'started_at' => $open['started'],
                    'ended_at' => Carbon::parse($doc->completed_at ?? $doc->updated_at ?? now()),
                ];
            }

            foreach ($segments as $seg) {
                DB::table('document_possessions')->insert(array_merge($seg, [
                    'document_id' => $docId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            DB::table('documents')->where('id', $docId)->update(['possession_started_at' => $possessionStart]);
        }
    }

    public function down(): void
    {
        // no-op: ledger rebuild is not reversible
    }
};
