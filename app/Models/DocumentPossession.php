<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPossession extends Model
{
    protected $fillable = [
        'document_id',
        'holder_id',
        'department_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Seconds this segment lasted (uses now() if still open). */
    public function seconds(): int
    {
        return (int) $this->started_at->diffInSeconds($this->ended_at ?? now());
    }
}
