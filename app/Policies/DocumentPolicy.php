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
     * Override authority — may act on a document even when not the current holder
     * or out of the normal flow. Limited to Super Admin and Department Head.
     * (Assistant Department Head can VIEW everything but cannot override actions.)
     */
    protected function canOverride(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Department Head']);
    }

    /**
     * Can the user SEE the document details / history?
     * Heads (Dept Head, Asst Head, Super Admin) see every document; everyone
     * else only sees documents that concern them. Powers the "QR not found" rule.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->can('documents.viewAll')) {
            return true; // executives / super admin: every department
        }
        if ($user->department_id && $document->department_id === $user->department_id) {
            return true; // members see everything in their own department
        }

        return $this->concerns($user, $document); // cross-department: only if forwarded/released to them
    }

    /** Only Super Admin can bring a finished document back to active (e.g. accidental completion). */
    public function reopen(User $user, Document $document): bool
    {
        return $user->hasRole('Super Admin') && $document->isClosed();
    }

    /** A recipient of a broadcast memo can acknowledge receipt once. */
    public function acknowledge(User $user, Document $document): bool
    {
        return $document->is_broadcast
            && $document->assignees()
                ->where('users.id', $user->id)
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

        // 2. Unclaimed transfer in my department
        return $this->isClaimable($document)
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

    /** Archive/complete also requires having received it first (or override). */
    public function archive(User $user, Document $document): bool
    {
        if (! $user->can('documents.archive') || $document->isClosed()) {
            return false;
        }

        return ($document->current_holder_id === $user->id && $document->status === 'received')
            || $this->canOverride($user);
    }

    /** Release is done by the encoder while still a draft with an assignee. */
    public function release(User $user, Document $document): bool
    {
        return $user->can('documents.release')
            && $document->status === 'draft'
            && $document->current_holder_id !== null
            && ($document->created_by === $user->id || $this->canOverride($user));
    }

    public function assign(User $user, Document $document): bool
    {
        // Never re-route a finished (archived/completed) document.
        if (! $user->can('documents.assign') || $document->isClosed()) {
            return false;
        }

        // Override roles (Super Admin / Department Head) may re-route any active document.
        if ($this->canOverride($user)) {
            return true;
        }

        // The encoder may (re)assign only while the document is still a draft OR
        // released-but-not-yet-received — to fix a mis-assignment before pickup —
        // AND only while it is still inside their own office. Once it has been
        // transferred to another office, that office owns routing (via claim → forward).
        return $document->created_by === $user->id
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
