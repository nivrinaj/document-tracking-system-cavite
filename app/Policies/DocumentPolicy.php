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
     * Can the user SEE the document details / history?
     * Heads (and Super Admin) can see every document in the department.
     * Everyone else only sees documents that concern them.
     * This is what powers the "QR not found" rule for the wrong user.
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

    /** The current holder can forward, but only once they have received it. */
    public function forward(User $user, Document $document): bool
    {
        return $user->can('documents.forward')
            && $document->current_holder_id === $user->id
            && in_array($document->status, ['received', 'forwarded']);
    }

    /** The current holder can archive/complete once received. */
    public function archive(User $user, Document $document): bool
    {
        return $user->can('documents.archive')
            && $document->current_holder_id === $user->id
            && in_array($document->status, ['received', 'forwarded']);
    }

    /** Release is done by receiving staff while still a draft with an assignee. */
    public function release(User $user, Document $document): bool
    {
        return $user->can('documents.release')
            && $document->status === 'draft'
            && $document->current_holder_id !== null
            && ($document->created_by === $user->id || $user->isHead());
    }

    public function assign(User $user, Document $document): bool
    {
        return $user->can('documents.assign')
            && in_array($document->status, ['draft'])
            && ($document->created_by === $user->id || $user->isHead());
    }

    public function update(User $user, Document $document): bool
    {
        return ($document->created_by === $user->id || $user->isHead())
            && $document->status === 'draft';
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.delete')
            && ($document->created_by === $user->id || $user->isHead());
    }
}
