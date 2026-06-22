<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Central place for the document life-cycle. Keeping the workflow here
 * (instead of spread across controllers) makes it easy to extend later.
 *
 *   encode -> assign -> release -> receive -> (forward -> receive ...) -> archive/complete
 */
class DocumentService
{
    /**
     * Transfer a document to another OFFICE (no specific person). It becomes an
     * unclaimed item that any receiver in that office can claim. Notifies them.
     */
    public function transferToOffice(Document $document, int $departmentId, User $actor, string $remarks): Document
    {
        return DB::transaction(function () use ($document, $departmentId, $actor, $remarks) {
            $dept = \App\Models\Department::find($departmentId);

            $document->update([
                'status' => 'released',
                'current_holder_id' => null,
                'department_id' => $departmentId,
                'division_id' => null,
                'released_at' => $document->released_at ?? now(),
                'is_pending' => false,
                'pending_at' => null,
            ]);
            $this->log($document, 'transferred', $actor, remarks: $remarks.' → '.($dept?->code ?? 'office').' (awaiting claim)');

            // Document now sits in the destination office's claim pool — time is
            // attributed to that office until one of its receivers claims it.
            $this->openPossession($document, null, $departmentId);

            // Alert the receivers of the destination office.
            $receivers = User::where('is_active', true)
                ->where('department_id', $departmentId)
                ->where('id', '!=', $actor->id)
                ->permission('documents.receive')
                ->get();
            foreach ($receivers as $r) {
                $r->notify(new \App\Notifications\DocumentRouted($document, 'transfer', $actor->name, $remarks));
            }

            return $document->refresh();
        });
    }

    /**
     * Broadcast a memo to every active staff member in a division or department.
     * No single holder — each recipient is a "concerned" assignee who acknowledges receipt.
     */
    public function broadcast(array $data, User $actor, string $scope): Document
    {
        return DB::transaction(function () use ($data, $actor, $scope) {
            $document = Document::create(array_merge($data, [
                'status' => 'released',
                'is_broadcast' => true,
                'created_by' => $actor->id,
                'current_holder_id' => null,
                'division_id' => $actor->division_id,
                'department_id' => $actor->department_id,
                'received_at' => $data['received_at'] ?? now(),
                'released_at' => now(),
            ]));
            $this->log($document, 'encoded', $actor, remarks: 'Memo encoded for broadcast.');

            $recipients = User::where('is_active', true)
                ->when($scope === 'division', fn ($q) => $q->where('division_id', $actor->division_id))
                ->when($scope === 'department', fn ($q) => $q->where('department_id', $actor->department_id))
                ->where('id', '!=', $actor->id)
                ->get();

            foreach ($recipients as $r) {
                $this->addAssignee($document, $r->id);
                $r->notify(new \App\Notifications\DocumentRouted($document, 'broadcast', $actor->name, $data['assign_remarks'] ?? null));
            }

            $this->log($document, 'released', $actor, remarks: 'Broadcast to '.$recipients->count().' staff ('.$scope.').');

            return $document->refresh();
        });
    }

    /** Bring a finished document back to active (Super Admin only). */
    public function reopen(Document $document, User $actor, ?string $remarks = null): Document
    {
        return DB::transaction(function () use ($document, $actor, $remarks) {
            $document->update([
                'status' => $document->current_holder_id ? 'received' : 'draft',
                'completed_at' => null,
            ]);
            $this->log($document, 'reopened', $actor, remarks: $remarks ?? 'Document reopened to active.');

            return $document->refresh();
        });
    }

    /** A recipient acknowledges receipt of a broadcast memo. */
    public function acknowledge(Document $document, User $user): void
    {
        $document->assignees()->updateExistingPivot($user->id, ['acknowledged_at' => now()]);
        $this->log($document, 'received', $user, remarks: 'Acknowledged receipt of the memo.');
    }

    private function divisionOf(int $userId): ?int
    {
        return User::find($userId)?->division_id;
    }

    /** Notify the recipient that a document is coming to them (skips self). */
    private function notify(?int $recipientId, User $actor, Document $document, string $verb, ?string $remarks): void
    {
        if (! $recipientId || $recipientId === $actor->id) {
            return;
        }

        $recipient = User::find($recipientId);
        $recipient?->notify(new \App\Notifications\DocumentRouted($document, $verb, $actor->name, $remarks));
    }

    /** Write one entry to the audit trail / history. */
    public function log(Document $document, string $action, ?User $actor, ?int $toUserId = null, ?int $fromUserId = null, ?string $remarks = null): DocumentLog
    {
        return $document->logs()->create([
            'action' => $action,
            'actor_id' => $actor?->id,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'remarks' => $remarks,
        ]);
    }

    /* ----------------------------------------------------------------
     | Possession ledger — attribute holding time to the physical holder.
     * ---------------------------------------------------------------- */

    /** Close the currently-open possession segment (if any). */
    private function closePossession(Document $document, ?\Illuminate\Support\Carbon $at = null): void
    {
        $document->possessions()->whereNull('ended_at')->update(['ended_at' => $at ?? now()]);
        $document->forceFill(['possession_started_at' => null])->saveQuietly();
    }

    /**
     * Start a new possession segment for a holder (or an office pool when
     * $holderId is null). Closes any segment still open first.
     */
    private function openPossession(Document $document, ?int $holderId, ?int $departmentId, ?\Illuminate\Support\Carbon $at = null): void
    {
        $at = $at ?? now();
        $this->closePossession($document, $at);
        $document->possessions()->create([
            'holder_id' => $holderId,
            'department_id' => $departmentId,
            'started_at' => $at,
            'ended_at' => null,
        ]);
        $document->forceFill(['possession_started_at' => $at])->saveQuietly();
    }

    /** Mark a user as a "concerned" party so they can track the document forever. */
    public function addAssignee(Document $document, ?int $userId): void
    {
        if ($userId) {
            $document->assignees()->syncWithoutDetaching([$userId]);
        }
    }

    /**
     * Create (encode) a new incoming document. Optionally assign it immediately.
     */
    public function encode(array $data, User $actor, ?int $assigneeId = null): Document
    {
        return DB::transaction(function () use ($data, $actor, $assigneeId) {
            // Vouchers use their voucher number as the tail of the tracking code.
            $trackingCode = null;
            if (strtolower($data['document_type'] ?? '') === 'voucher' && ! empty($data['voucher_number'])) {
                $trackingCode = Document::trackingCodeForVoucher($data['voucher_number']);
            }

            $document = Document::create(array_merge($data, array_filter([
                'tracking_code' => $trackingCode,
            ]), [
                'status' => 'draft',
                'created_by' => $actor->id,
                'division_id' => $data['division_id'] ?? $actor->division_id,
                'department_id' => $data['department_id'] ?? $actor->department_id,
                'received_at' => $data['received_at'] ?? now(),
            ]));

            $this->addAssignee($document, $actor->id);
            $this->log($document, 'encoded', $actor, remarks: $data['encode_remarks'] ?? 'Document encoded.');

            // The encoder physically holds the document from this moment until the
            // intended recipient receives it — so timing starts against the encoder.
            $this->openPossession($document, $actor->id, $document->department_id);

            if ($assigneeId) {
                $this->assign($document, $assigneeId, $actor, $data['assign_remarks'] ?? null);
            }

            return $document->refresh();
        });
    }

    /** Assign / re-assign the document to a staff member (before release). */
    public function assign(Document $document, int $assigneeId, User $actor, ?string $remarks = null): Document
    {
        return DB::transaction(function () use ($document, $assigneeId, $actor, $remarks) {
            // If the document was already released, re-routing it should alert the new holder.
            $wasReleased = $document->status === 'released';

            $holder = User::find($assigneeId);
            $document->update([
                'current_holder_id' => $assigneeId,
                'division_id' => $holder?->division_id ?? $document->division_id,
                'department_id' => $holder?->department_id ?? $document->department_id,
            ]);
            $this->addAssignee($document, $assigneeId);
            $this->log($document, 'assigned', $actor, toUserId: $assigneeId, remarks: $remarks ?? 'Document assigned.');

            if ($wasReleased) {
                $this->notify($assigneeId, $actor, $document, 'assigned', $remarks);
            }

            return $document->refresh();
        });
    }

    /** Release the document — receiving staff hands over the printed QR. */
    public function release(Document $document, User $actor, ?string $remarks = null): Document
    {
        return DB::transaction(function () use ($document, $actor, $remarks) {
            $document->update([
                'status' => 'released',
                'released_at' => now(),
            ]);
            $this->log($document, 'released', $actor, toUserId: $document->current_holder_id, remarks: $remarks ?? 'Document released.');
            $this->notify($document->current_holder_id, $actor, $document, 'released', $remarks);

            return $document->refresh();
        });
    }

    /** The intended recipient confirms physical receipt (after scanning the QR). */
    public function receive(Document $document, User $actor, ?string $remarks = null): Document
    {
        return DB::transaction(function () use ($document, $actor, $remarks) {
            $document->update([
                'status' => 'received',
                'current_holder_id' => $actor->id,
                'department_id' => $actor->department_id ?? $document->department_id,
                'division_id' => $actor->division_id ?? $document->division_id,
                'received_at' => $document->received_at ?? now(),
                'is_pending' => false,
                'pending_at' => null,
            ]);
            $this->addAssignee($document, $actor->id);
            $this->log($document, 'received', $actor, remarks: $remarks ?? 'Document physically received.');

            // Possession now counts towards the receiver.
            $this->openPossession($document, $actor->id, $actor->department_id ?? $document->department_id);

            return $document->refresh();
        });
    }

    /** Forward the document to another staff member. Always requires remarks. */
    public function forward(Document $document, int $toUserId, User $actor, string $remarks): Document
    {
        return DB::transaction(function () use ($document, $toUserId, $actor, $remarks) {
            $from = $document->current_holder_id;
            $to = User::find($toUserId);
            $document->update([
                'status' => 'forwarded',
                'current_holder_id' => $toUserId,
                'division_id' => $to?->division_id ?? $document->division_id,
                'department_id' => $to?->department_id ?? $document->department_id,
                'is_pending' => false,
                'pending_at' => null,
            ]);
            // Possession transfers when the recipient RECEIVES it, not on forward —
            // the forwarder still physically holds it while in transit.
            $this->addAssignee($document, $toUserId);
            $this->log($document, 'forwarded', $actor, toUserId: $toUserId, fromUserId: $from, remarks: $remarks);
            $this->notify($toUserId, $actor, $document, 'forwarded', $remarks);

            return $document->refresh();
        });
    }

    /** Archive / complete the document. Always requires remarks. */
    public function archive(Document $document, User $actor, string $remarks, bool $completed = false): Document
    {
        return DB::transaction(function () use ($document, $actor, $remarks, $completed) {
            $document->update([
                'status' => $completed ? 'completed' : 'archived',
                'completed_at' => now(),
                'is_pending' => false,
                'pending_at' => null,
            ]);
            $this->log($document, $completed ? 'completed' : 'archived', $actor, remarks: $remarks);

            // Close out the possession clock — the document's life has ended.
            $this->closePossession($document);

            return $document->refresh();
        });
    }

    /**
     * Mark a document as PENDING — the holder is waiting on the origin / someone
     * else, so their possession clock is paused (and it drops out of the aging
     * report). The holder keeps the document until they resume or forward it.
     */
    public function markPending(Document $document, User $actor, string $remarks): Document
    {
        return DB::transaction(function () use ($document, $actor, $remarks) {
            $document->update(['is_pending' => true, 'pending_at' => now()]);
            $this->closePossession($document); // pause the clock — no open segment
            $this->log($document, 'pending', $actor, remarks: $remarks);

            return $document->refresh();
        });
    }

    /** Resume work on a paused document — the clock starts again for the holder. */
    public function resume(Document $document, User $actor, ?string $remarks = null): Document
    {
        return DB::transaction(function () use ($document, $actor, $remarks) {
            $document->update(['is_pending' => false, 'pending_at' => null]);
            $this->openPossession($document, $document->current_holder_id, $document->department_id);
            $this->log($document, 'resumed', $actor, remarks: $remarks ?? 'Work resumed.');

            return $document->refresh();
        });
    }

    /**
     * Pending + RETURN to another office (only when cross-office routing is on).
     * The clock pauses now; it resumes — counting against the destination office —
     * only once one of their receivers claims/receives it.
     */
    public function pendingReturn(Document $document, int $departmentId, User $actor, string $remarks): Document
    {
        return DB::transaction(function () use ($document, $departmentId, $actor, $remarks) {
            $dept = \App\Models\Department::find($departmentId);

            // Pause the clock first, then move it to the destination office's pool.
            $this->closePossession($document);
            $document->update([
                'status' => 'released',
                'current_holder_id' => null,
                'department_id' => $departmentId,
                'division_id' => null,
                'is_pending' => true,
                'pending_at' => now(),
                'released_at' => $document->released_at ?? now(),
            ]);
            $this->log($document, 'pending', $actor, remarks: $remarks.' → returned to '.($dept?->code ?? 'office').' (awaiting their action)');

            $receivers = User::where('is_active', true)
                ->where('department_id', $departmentId)
                ->where('id', '!=', $actor->id)
                ->permission('documents.claim')
                ->get();
            foreach ($receivers as $r) {
                $r->notify(new \App\Notifications\DocumentRouted($document, 'transfer', $actor->name, $remarks));
            }

            return $document->refresh();
        });
    }

    /**
     * Send a document to a hand-picked list of people — possibly across several
     * offices. Like a memo: each recipient is a concerned party who acknowledges
     * receipt individually (tracked via the acknowledged_at pivot).
     */
    public function broadcastToUsers(array $data, User $actor, array $userIds): Document
    {
        return DB::transaction(function () use ($data, $actor, $userIds) {
            $document = Document::create(array_merge($data, [
                'status' => 'released',
                'is_broadcast' => true,
                'created_by' => $actor->id,
                'current_holder_id' => null,
                'division_id' => $actor->division_id,
                'department_id' => $actor->department_id,
                'received_at' => $data['received_at'] ?? now(),
                'released_at' => now(),
            ]));
            $this->log($document, 'encoded', $actor, remarks: 'Memo encoded for selected recipients.');

            $recipients = User::where('is_active', true)
                ->whereIn('id', $userIds)
                ->where('id', '!=', $actor->id)
                ->get();

            foreach ($recipients as $r) {
                $this->addAssignee($document, $r->id);
                $r->notify(new \App\Notifications\DocumentRouted($document, 'broadcast', $actor->name, $data['assign_remarks'] ?? null));
            }

            $this->log($document, 'released', $actor, remarks: 'Sent to '.$recipients->count().' selected recipient(s).');

            return $document->refresh();
        });
    }
}
