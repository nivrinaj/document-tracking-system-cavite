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
        return $user->isHead() || $this->concerns($user, $document);
    }

    /** Only the current holder, when the document was released/forwarded TO them. */
    public function receive(User $user, Document $document): bool
    {
        return $user->can('documents.receive')
            && $document->current_holder_id === $user->id
            && in_array($document->status, ['released', 'forwarded']);
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
        // The encoder may (re)assign while the document is still a draft OR
        // released-but-not-yet-received (to fix a mis-assignment before pickup).
        // Once received/forwarded, only an override role can re-route it.
        return $user->can('documents.assign')
            && (in_array($document->status, ['draft', 'released']) || $this->canOverride($user))
            && ($document->created_by === $user->id || $this->canOverride($user));
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
