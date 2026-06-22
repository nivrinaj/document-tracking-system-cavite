<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'title',
        'status',
        'remarks',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'cleared' => 'Cleared',
            'rejected' => 'Rejected — returned',
            default => 'Pending',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'cleared' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
