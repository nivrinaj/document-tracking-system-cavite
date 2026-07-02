<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Whether the user is "concerned" with the document:
     * they encoded it, currently hold it, or have ever been assigned it.
     */
    protected function concerns(User $user, Document $document): bool
    {
        return $document->created_by === $user->id
            || $document->current_holder_id === $user->id
            || $document->assignees()->whereKey($user->id)->exists();
    }

    /**
     * Override authority — may act on a document even when not the current holder.
     * Restricted to Super Admin ONLY. Everyone else (including Department Heads)
     * can only act on a document once they PHYSICALLY hold it — i.e. after they
     * have received it by scanning the QR. This prevents action buttons from
     * showing to people a document was merely assigned/released to.
     */
    protected function canOverride(User $user): bool
    {
        return $user->hasSystemRole(User::SYS_SUPER_ADMIN);
    }

    /**
     * Can the user SEE the document details / history? Role-scoped:
     *  - Executives (viewAll): every document.
     *  - Department Head / Asst Head: their whole department + anything concerning
     *    their department's staff (even if it moved to another office).
     *  - Division Head: only their division's documents + anything concerning their
     *    division's staff.
     *  - Everyone else: only documents that concern them.
     * Always: anything that concerns the user personally (cross-office included).
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->can('documents.viewAll')) {
            return true;
        }
        if ($this->concerns($user, $document)) {
            return true; // personally concerned — even across offices
        }

        // Department head / assistant head: the entire own department.
        if ($user->isDeptHeadRole() && $user->department_id) {
            return $document->department_id === $user->department_id
                || $document->creator?->department_id === $user->department_id
                || $document->assignees()->where('users.department_id', $user->department_id)->exists();
        }

        // Division head: only their own division's scope.
        if ($user->isDivisionHead() && $user->division_id) {
            return ($document->division_id === $user->division_id && $document->department_id === $user->department_id)
                || $document->creator?->division_id === $user->division_id
                || $document->assignees()->where('users.division_id', $user->division_id)->exists();
        }

        // Sitting in the Department Head queue: every active staff member in that
        // same department can see it (otherwise they could never find the "Get
        // from Department Head" button to claim it).
        if ($document->isAwaitingHeadClaim() && $user->department_id === $document->department_id) {
            return true;
        }

        return false;
    }

    /** Only Super Admin can bring a finished document back to active (e.g. accidental completion). */
    public function reopen(User $user, Document $document): bool
    {
        return $user->hasSystemRole(User::SYS_SUPER_ADMIN) && $document->isClosed();
    }

    /**
     * A user who was ASKED to acknowledge (ack_requested_at set) can acknowledge
     * once, until they have. This is independent of whether the document is a
     * broadcast — distributing a held document also asks specific people to ack.
     */
    public function acknowledge(User $user, Document $document): bool
    {
        // While paused (pending), nobody can acknowledge until it is resumed.
        if ($document->isClosed() || $document->is_pending) {
            return false;
        }

        return $document->assignees()
            ->where('users.id', $user->id)
            ->wherePivotNotNull('ack_requested_at')
            ->wherePivotNull('acknowledged_at')
            ->exists();
    }

    /**
     * Receive applies in two cases:
     *  1. Direct — the document was released/forwarded TO me, and
     *  2. Pool claim — an unclaimed transfer sitting in my office (no holder yet),
     *     which any receiver in that department may claim.
     */
    public function receive(User $user, Document $document): bool
    {
        if (! $user->can('documents.receive') || $document->is_broadcast || $document->isClosed()) {
            return false;
        }

        // 1. Directly assigned to me
        if ($document->current_holder_id === $user->id && in_array($document->status, ['released', 'forwarded'])) {
            return true;
        }

        // 2. Unclaimed transfer in my department — requires the claim capability
        return $user->canClaimFromOffice()
            && $this->isClaimable($document)
            && $user->department_id
            && $document->department_id === $user->department_id;
    }

    /** An unclaimed office transfer awaiting a receiver. */
    public function isClaimable(Document $document): bool
    {
        return $document->current_holder_id === null
            && $document->status === 'released'
            && ! $document->is_broadcast;
    }

    /**
     * Forward requires the holder to have RECEIVED it first (status = received),
     * which keeps the audit trail intact. Override roles may force it.
     */
    public function forward(User $user, Document $document): bool
    {
        if (! $user->can('documents.forward') || $document->isClosed()) {
            return false;
        }

        return ($document->current_holder_id === $user->id && $document->status === 'received')
            || $this->canOverride($user);
    }

    /**
     * Forward specifically to the Department Head — opt-in per office
     * (departments.forward_to_head_enabled), only when that office actually has
     * a Department Head, and never offered to the head forwarding to themselves.
     */
    public function forwardToHead(User $user, Document $document): bool
    {
        if (! $this->forward($user, $document)) {
            return false;
        }
        if (! optional($document->department)->forward_to_head_enabled) {
            return false;
        }
        $head = $document->departmentHead();

        return $head && $head->id !== $user->id;
    }

    /**
     * Claim a document currently sitting with the Department Head — any other
     * active staff member in the SAME department (never the head themselves,
     * who uses the normal Receive button instead).
     */
    public function claimFromHead(User $user, Document $document): bool
    {
        if ($document->isClosed() || ! $user->can('documents.receive')) {
            return false;
        }
        if (! optional($document->department)->forward_to_head_enabled) {
            return false;
        }
        if (! $document->isAwaitingHeadClaim()) {
            return false;
        }

        return $document->departmentHead()?->id !== $user->id
            && $user->department_id === $document->department_id;
    }

    /** Archive/complete also requires having received it first (or override). */
    public function archive(User $user, Document $document): bool
    {
        if (! $user->can('documents.archive') || $document->isClosed()) {
            return false;
        }

        return ($document->current_holder_id === $user->id && $document->status === 'received')
            || $this->canOverride($user);
    }

    /**
     * Pause work on a document the holder currently has (status received).
     * Pausing stops their possession clock until they resume / forward it.
     */
    public function pending(User $user, Document $document): bool
    {
        if ($document->isClosed() || $document->is_pending) {
            return false;
        }

        return ($document->current_holder_id === $user->id && $document->status === 'received')
            || $this->canOverride($user);
    }

    /**
     * Distribute an existing document to several people for acknowledgement
     * (selected staff, a division, or the whole department). Available to the
     * current holder once they've received it — even after it has passed others.
     */
    public function distribute(User $user, Document $document): bool
    {
        if ($document->isClosed()) {
            return false;
        }

        return ($document->current_holder_id === $user->id && $document->status === 'received')
            || $this->canOverride($user);
    }

    /** Resume a paused document — only the holder (or an override role). */
    public function resume(User $user, Document $document): bool
    {
        if (! $document->is_pending || $document->isClosed()) {
            return false;
        }

        return $document->current_holder_id === $user->id || $this->canOverride($user);
    }

    /**
     * Send the document to ANOTHER office's claim pool. This is a receiving-staff
     * privilege (they hold documents.release); regular staff can only forward
     * within their own office.
     */
    public function transfer(User $user, Document $document): bool
    {
        if (! $user->canTransferOffice() || $document->isClosed()) {
            return false;
        }

        return ($document->current_holder_id === $user->id && $document->status === 'received')
            || $this->canOverride($user);
    }

    /** Release is done by the encoder (someone with encode rights) on their own draft. */
    public function release(User $user, Document $document): bool
    {
        if ($document->status !== 'draft' || $document->current_holder_id === null) {
            return false;
        }

        return ($user->canEncode() && $document->created_by === $user->id) || $this->canOverride($user);
    }

    public function assign(User $user, Document $document): bool
    {
        // Never re-route a finished (archived/completed) document.
        if ($document->isClosed()) {
            return false;
        }
        if ($this->canOverride($user)) {
            return true;
        }

        // The encoder may (re)assign their own draft (or released-but-not-received) while
        // it is still inside their office — to fix a mis-assignment before pickup.
        return $user->canEncode()
            && $document->created_by === $user->id
            && in_array($document->status, ['draft', 'released'])
            && $document->department_id === $user->department_id;
    }

    public function update(User $user, Document $document): bool
    {
        return ($document->created_by === $user->id || $this->canOverride($user))
            && $document->status === 'draft';
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.delete')
            && ($document->created_by === $user->id || $this->canOverride($user));
    }
}
