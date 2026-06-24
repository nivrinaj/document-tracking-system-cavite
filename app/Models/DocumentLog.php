<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'action',
        'actor_id',
        'from_user_id',
        'to_user_id',
        'remarks',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'encoded' => 'Encoded',
            'assigned' => 'Assigned',
            'released' => 'Released',
            'received' => 'Received',
            'forwarded' => 'Forwarded',
            'transferred' => 'Transferred to office',
            'pending' => 'Marked pending',
            'resumed' => 'Resumed',
            'item_cleared' => 'Item cleared',
            'item_rejected' => 'Item rejected',
            'distributed' => 'Distributed',
            'attached' => 'Attachment added',
            'rejected' => 'Rejected / returned',
            'reopened' => 'Reopened',
            'archived' => 'Archived',
            'completed' => 'Completed',
            default => ucfirst($this->action),
        };
    }

    public function actionColor(): string
    {
        return match ($this->action) {
            'encoded' => 'gray',
            'assigned' => 'purple',
            'released' => 'amber',
            'received' => 'blue',
            'forwarded', 'transferred' => 'indigo',
            'pending' => 'amber',
            'resumed' => 'blue',
            'item_cleared' => 'green',
            'item_rejected' => 'red',
            'distributed' => 'indigo',
            'attached' => 'blue',
            'rejected' => 'red',
            'archived', 'completed' => 'green',
            default => 'gray',
        };
    }
}
