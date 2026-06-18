<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_code',
        'title',
        'reference_no',
        'document_type',
        'voucher_number',
        'description',
        'source',
        'priority',
        'status',
        'division_id',
        'created_by',
        'current_holder_id',
        'received_at',
        'released_at',
        'completed_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'released_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Auto-generate a unique tracking code when creating a document.
     */
    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            if (empty($document->tracking_code)) {
                $document->tracking_code = static::generateTrackingCode();
            }
        });
    }

    public static function generateTrackingCode(): string
    {
        // e.g. PGC-2026-3F9K2A  (human-readable + unique)
        do {
            $code = 'PGC-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (static::where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * For vouchers we use the voucher number itself as the tail of the code,
     * so staff can recognise it at a glance: PGC-2026-{voucher_number}.
     */
    public static function trackingCodeForVoucher(string $voucherNumber): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $voucherNumber));

        return 'PGC-'.now()->format('Y').'-'.$clean;
    }

    /* ----------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------- */

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_holder_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentLog::class)->latest();
    }

    /** All staff who have ever been assigned/held this document. */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_assignees')->withTimestamps();
    }

    /* ----------------------------------------------------------------
     | Helpers
     * ---------------------------------------------------------------- */

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'released' => 'amber',
            'received' => 'blue',
            'forwarded' => 'indigo',
            'archived', 'completed' => 'green',
            default => 'gray',
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'blue',
            'low' => 'gray',
            default => 'gray',
        };
    }

    /** Is this document finished (archived/completed)? */
    public function isClosed(): bool
    {
        return in_array($this->status, ['archived', 'completed']);
    }

    public function isVoucher(): bool
    {
        return strtolower($this->document_type) === 'voucher';
    }

    /** When did the document last change hands / status? */
    public function lastActionAt(): ?\Illuminate\Support\Carbon
    {
        return $this->updated_at;
    }

    /** Human elapsed time since the last action, e.g. "3 hours". */
    public function elapsedSinceLastAction(): string
    {
        return optional($this->lastActionAt())->diffForHumans(['parts' => 2, 'short' => false]) ?? '—';
    }

    /** Total turnaround for a finished document (received -> completed). */
    public function turnaround(): ?string
    {
        if ($this->received_at && $this->completed_at) {
            return $this->received_at->diffForHumans($this->completed_at, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
        }

        return null;
    }

    /** Age of the document since it was first received by the department. */
    public function age(): string
    {
        $since = $this->received_at ?? $this->created_at;

        return $since?->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) ?? '—';
    }

    /**
     * Colour hint for how long a still-open document has been sitting untouched.
     */
    public function agingColor(): string
    {
        if ($this->isClosed() || ! $this->updated_at) {
            return 'gray';
        }
        $hours = $this->updated_at->diffInHours(now());

        return match (true) {
            $hours >= 72 => 'red',
            $hours >= 24 => 'amber',
            default => 'green',
        };
    }
}
