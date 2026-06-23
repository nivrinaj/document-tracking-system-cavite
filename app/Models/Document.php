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
        'is_pending',
        'pending_at',
        'is_broadcast',
        'division_id',
        'department_id',
        'created_by',
        'current_holder_id',
        'received_at',
        'released_at',
        'completed_at',
        'possession_started_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'released_at' => 'datetime',
        'completed_at' => 'datetime',
        'pending_at' => 'datetime',
        'possession_started_at' => 'datetime',
        'is_broadcast' => 'boolean',
        'is_pending' => 'boolean',
    ];

    /** Whether the priority feature is enabled system-wide. */
    public static function priorityEnabled(): bool
    {
        return \App\Models\Setting::get('enable_priority', '0') === '1';
    }

    /** Whether the multi-item "route slip" feature is enabled system-wide. */
    public static function routeItemsEnabled(): bool
    {
        return \App\Models\Setting::get('enable_route_items', '0') === '1';
    }

    /** Human-friendly label for a status value (the DB value stays 'draft'). */
    public static function statusLabel(?string $status): string
    {
        return [
            'draft' => 'Pending Release',
        ][$status] ?? ucfirst((string) $status);
    }

    /** Label for this document's own status. */
    public function statusName(): string
    {
        return static::statusLabel($this->status);
    }

    /** Re-key a [status => count] array using friendly labels (for charts/legends). */
    public static function relabelStatuses(array $counts): array
    {
        $out = [];
        foreach ($counts as $k => $v) {
            $out[static::statusLabel($k)] = $v;
        }

        return $out;
    }

    /**
     * Limit a query to documents the given user may see:
     *  - viewAll permission  -> every department
     *  - otherwise           -> their own department + any document concerning them
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->can('documents.viewAll')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            if ($user->department_id) {
                $q->where('documents.department_id', $user->department_id);
            }
            $q->orWhere('created_by', $user->id)
                ->orWhere('current_holder_id', $user->id)
                ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id));
        });
    }

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

    public static function trackingPrefix(): string
    {
        return \App\Models\Setting::get('tracking_prefix', 'PGC') ?: 'PGC';
    }

    public static function generateTrackingCode(): string
    {
        // e.g. PGC-2026-3F9K2A  (human-readable + unique)
        $prefix = static::trackingPrefix();
        do {
            $code = $prefix.'-'.now()->format('Y').'-'.strtoupper(Str::random(6));
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

        return static::trackingPrefix().'-'.now()->format('Y').'-'.$clean;
    }

    /* ----------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------- */

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
        // Newest action first so the trail reads top-to-bottom without scrolling.
        return $this->hasMany(DocumentLog::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /** All staff who have ever been assigned/held this document. */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_assignees')->withPivot('acknowledged_at', 'ack_requested_at')->withTimestamps();
    }

    /** Possession ledger — every period the document sat with a holder / office pool. */
    public function possessions(): HasMany
    {
        return $this->hasMany(DocumentPossession::class)->orderBy('started_at');
    }

    /** Route-slip line items (individual sub-documents carried by this slip). */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('id');
    }

    /** The currently-open possession segment (null when paused/pending). */
    public function openPossession(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DocumentPossession::class)->whereNull('ended_at')->latestOfMany('started_at');
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

    /* ----------------------------------------------------------------
     | Possession-based timing (who holds it & for how long)
     * ---------------------------------------------------------------- */

    /** Seconds the current holder has physically had the document (0 when paused). */
    public function secondsWithCurrentHolder(): int
    {
        if ($this->is_pending || ! $this->possession_started_at) {
            return 0;
        }

        return (int) $this->possession_started_at->diffInSeconds(now());
    }

    /** Human "time with current holder", e.g. "2 days 3 hrs". */
    public function timeWithCurrentHolder(): string
    {
        if ($this->is_pending) {
            return 'Paused (pending)';
        }
        if (! $this->possession_started_at) {
            return '—';
        }

        return static::humanDuration($this->secondsWithCurrentHolder());
    }

    /** The person physically holding the document now (from the open ledger segment). */
    public function currentPossessor(): ?User
    {
        return $this->openPossession?->holder;
    }

    /** Label for who physically holds the document — a person, or an office pool. */
    public function possessorLabel(): string
    {
        if ($p = $this->currentPossessor()) {
            return $p->name;
        }
        if ($this->is_pending) {
            return 'Pending (no one)';
        }

        return 'Office pool · '.($this->openPossession?->department?->code ?? $this->department?->code ?? '—');
    }

    /** Total seconds since the document was first encoded. */
    public function totalSeconds(): int
    {
        $since = $this->created_at;

        return $since ? (int) $since->diffInSeconds($this->completed_at ?? now()) : 0;
    }

    /** Human total lifetime of the document. */
    public function totalTime(): string
    {
        return static::humanDuration($this->totalSeconds());
    }

    /**
     * Format a number of seconds into a compact, human duration:
     *   "45 mins", "3 hrs 10 mins", "2 days 4 hrs".
     */
    public static function humanDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '—';
        }
        if ($seconds < 60) {
            return $seconds.' sec'.($seconds === 1 ? '' : 's');
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes.' min'.($minutes === 1 ? '' : 's');
        }

        $hours = intdiv($minutes, 60);
        $remMin = $minutes % 60;
        if ($hours < 24) {
            return $remMin > 0
                ? "{$hours} hr".($hours === 1 ? '' : 's')." {$remMin} min".($remMin === 1 ? '' : 's')
                : "{$hours} hr".($hours === 1 ? '' : 's');
        }

        $days = intdiv($hours, 24);
        $remHr = $hours % 24;

        return $remHr > 0
            ? "{$days} day".($days === 1 ? '' : 's')." {$remHr} hr".($remHr === 1 ? '' : 's')
            : "{$days} day".($days === 1 ? '' : 's');
    }
}
