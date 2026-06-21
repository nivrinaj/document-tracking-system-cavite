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
        $title = $this->document->title;

        $message = match ($this->verb) {
            'broadcast' => "📣 New memo{$by}: {$title}",
            'transfer' => "📥 A document was transferred to your office to claim{$by}: {$title}",
            'forwarded' => "A document was forwarded to you{$by}: {$title}",
            'assigned' => "A document was assigned to you{$by}: {$title}",
            'released' => "A document was released to you{$by}: {$title}",
            default => "A document was sent to you{$by}: {$title}",
        };

        return [
            'document_id' => $this->document->id,
            'tracking_code' => $this->document->tracking_code,
            'title' => $title,
            'verb' => $this->verb,
            'message' => $message,
            'remarks' => $this->remarks,
            'url' => route('documents.show', $this->document),
        ];
    }
}
