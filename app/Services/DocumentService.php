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
    private function divisionOf(int $userId): ?int
    {
        return User::find($userId)?->division_id;
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
                'received_at' => $data['received_at'] ?? now(),
            ]));

            $this->addAssignee($document, $actor->id);
            $this->log($document, 'encoded', $actor, remarks: $data['encode_remarks'] ?? 'Document encoded.');

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
            $document->update([
                'current_holder_id' => $assigneeId,
                'division_id' => $this->divisionOf($assigneeId) ?? $document->division_id,
            ]);
            $this->addAssignee($document, $assigneeId);
            $this->log($document, 'assigned', $actor, toUserId: $assigneeId, remarks: $remarks ?? 'Document assigned.');

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
            ]);
            $this->addAssignee($document, $actor->id);
            $this->log($document, 'received', $actor, remarks: $remarks ?? 'Document physically received.');

            return $document->refresh();
        });
    }

    /** Forward the document to another staff member. Always requires remarks. */
    public function forward(Document $document, int $toUserId, User $actor, string $remarks): Document
    {
        return DB::transaction(function () use ($document, $toUserId, $actor, $remarks) {
            $from = $document->current_holder_id;
            $document->update([
                'status' => 'forwarded',
                'current_holder_id' => $toUserId,
                'division_id' => $this->divisionOf($toUserId) ?? $document->division_id,
            ]);
            $this->addAssignee($document, $toUserId);
            $this->log($document, 'forwarded', $actor, toUserId: $toUserId, fromUserId: $from, remarks: $remarks);

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
            ]);
            $this->log($document, $completed ? 'completed' : 'archived', $actor, remarks: $remarks);

            return $document->refresh();
        });
    }
}
