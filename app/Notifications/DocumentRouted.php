<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Notification;

/**
 * Sent to a staff member when a document is released or forwarded to them.
 * Stored in the database and shown in the in-app notification bell.
 */
class DocumentRouted extends Notification
{
    public function __construct(
        public Document $document,
        public string $verb,          // "released" | "forwarded" | "assigned"
        public ?string $byName = null,
        public ?string $remarks = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $by = $this->byName ? " by {$this->byName}" : '';

        return [
            'document_id' => $this->document->id,
            'tracking_code' => $this->document->tracking_code,
            'title' => $this->document->title,
            'verb' => $this->verb,
            'message' => "A document was {$this->verb} to you{$by}: {$this->document->title}",
            'remarks' => $this->remarks,
            'url' => route('documents.show', $this->document),
        ];
    }
}
