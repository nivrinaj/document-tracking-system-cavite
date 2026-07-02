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
        'fund_id',
        'amount',
        'obr_no',
        'responsibility_center_id',
        'responsibility_center_project_id',
        'rc_code',
        'nature_of_transaction',
        'is_hospital',
        'description',
        'source',
        'priority',
        'deadline',
        'status',
        'is_pending',
        'pending_at',
        'is_broadcast',
        'distribution_summary',
        'division_id',
        'department_id',
        'created_by',
        'current_holder_id',
        'received_at',
        'released_at',
        'completed_at',
        'possession_started_at',
        'is_transmittal',
        'transmittal_quantity',
        'forwarded_to_head_at',
        'time_tracking_mode',
        'calendar_days_include_weekends',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'released_at' => 'datetime',
        'completed_at' => 'datetime',
        'pending_at' => 'datetime',
        'possession_started_at' => 'datetime',
        'forwarded_to_head_at' => 'datetime',
        'deadline' => 'date',
        'is_broadcast' => 'boolean',
        'is_pending' => 'boolean',
        'is_hospital' => 'boolean',
        'is_transmittal' => 'boolean',
        'calendar_days_include_weekends' => 'boolean',
        'amount' => 'decimal:2',
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

    /** Whether the batch-receive page is enabled system-wide. */
    public static function batchReceiveEnabled(): bool
    {
        return \App\Models\Setting::get('enable_batch_receive', '1') === '1';
    }

    /** Whether linking related documents is enabled system-wide. */
    public static function linkingEnabled(): bool
    {
        return \App\Models\Setting::get('enable_document_linking', '1') === '1';
    }

    /** Whether "Supporting Documents" are enabled system-wide. */
    public static function attachmentsEnabled(): bool
    {
        return \App\Models\Setting::get('enable_attachments', '0') === '1';
    }

    /** Whether the encoder's "Digital Copy" upload is enabled system-wide. */
    public static function digitalCopyEnabled(): bool
    {
        return \App\Models\Setting::get('enable_digital_copy', '0') === '1';
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

        $isDeptHead = $user->isDeptHeadRole() && $user->department_id;
        $isDivHead = $user->isDivisionHead() && $user->division_id;

        return $query->where(function ($q) use ($user, $isDeptHead, $isDivHead) {
            // Always: documents that concern the user personally (cross-office included).
            $q->where('created_by', $user->id)
                ->orWhere('current_holder_id', $user->id)
                ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id));

            // Sitting in the Department Head queue: every active staff member in that
            // same department, so they can find it and "Get from Department Head".
            if ($user->department_id) {
                $q->orWhere(function ($w) use ($user) {
                    $w->where('documents.department_id', $user->department_id)
                        ->where('documents.status', 'forwarded')
                        ->whereNotNull('documents.forwarded_to_head_at')
                        ->whereHas('currentHolder.roles', fn ($r) => $r->where('system_key', User::SYS_DEPARTMENT_HEAD));
                });
            }

            if ($isDeptHead) {
                $q->orWhere('documents.department_id', $user->department_id)
                    ->orWhereHas('creator', fn ($c) => $c->where('department_id', $user->department_id))
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.department_id', $user->department_id));
            } elseif ($isDivHead) {
                $q->orWhere(fn ($w) => $w->where('documents.division_id', $user->division_id)->where('documents.department_id', $user->department_id))
                    ->orWhereHas('creator', fn ($c) => $c->where('division_id', $user->division_id))
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.division_id', $user->division_id));
            }
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

    /**
     * Accounting fund-based code: [FundCode]-[YYYY]-[MM]-[seq](-H).
     * The sequence resets annually and is shared by General/SEF/Trust ('STD'); the
     * 20% Development Fund ('DEV') and the Hospital division ('HOSP') each have their
     * own. Hospital documents get an "-H" suffix.
     */
    public static function generateFundCode(Fund $fund, bool $hospital): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $seq = TrackingSequence::next($year, $fund->sequenceKey($hospital));

        return $fund->code.'-'.$year.'-'.$month.'-'.$seq.($hospital ? '-H' : '');
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

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function responsibilityCenter(): BelongsTo
    {
        return $this->belongsTo(ResponsibilityCenter::class);
    }

    public function responsibilityCenterProject(): BelongsTo
    {
        return $this->belongsTo(ResponsibilityCenterProject::class);
    }

    /** "1011/OPG - SPA - Project 1" style combined RC label for reports & display. */
    public function rcLabel(): ?string
    {
        $rc = $this->responsibilityCenter;
        if (! $rc) {
            return $this->rc_code ?: null;
        }
        $label = $rc->label();
        if ($proj = $this->responsibilityCenterProject) {
            $label .= ' - '.$proj->label();
        }

        return $label;
    }

    public function currentHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_holder_id');
    }

    /** The Department Head for this document's CURRENT department (by role + department_id FK, never by name). */
    public function departmentHead(): ?User
    {
        return static::departmentHeadFor($this->department_id);
    }

    /** The Department Head user for a given department (by role's stable system_key + department_id FK, never by name), if any. */
    public static function departmentHeadFor(?int $departmentId): ?User
    {
        if (! $departmentId) {
            return null;
        }

        return User::where('is_active', true)
            ->where('department_id', $departmentId)
            ->whereHas('roles', fn ($q) => $q->where('system_key', User::SYS_DEPARTMENT_HEAD))
            ->first();
    }

    /** Sitting with the Department Head, forwarded there via forwardToHead(), not yet claimed/received by anyone. */
    public function isAwaitingHeadClaim(): bool
    {
        if (! $this->forwarded_to_head_at || $this->status !== 'forwarded') {
            return false;
        }
        $head = $this->departmentHead();

        return $head && $this->current_holder_id === $head->id;
    }

    /** How long the document has been waiting in the Department Head queue, honoring this office's time-tracking mode. */
    public function headQueueWaitSeconds(): ?int
    {
        if (! $this->forwarded_to_head_at) {
            return null;
        }

        return $this->elapsedSeconds($this->forwarded_to_head_at, now());
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

    /** All attachment rows (supporting documents + digital copy). */
    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class)->orderBy('id');
    }

    /** Supporting documents (title required, file optional) — drive the handover checklist. */
    public function supportingDocuments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class)->where('kind', 'supporting')->orderBy('id');
    }

    /** The encoder's single digital copy of the document, if uploaded. */
    public function digitalCopy(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DocumentAttachment::class)->where('kind', 'digital_copy')->latest('id');
    }

    /** Other documents linked to this one (stored symmetrically). */
    public function relatedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_links', 'document_id', 'related_document_id')->withTimestamps();
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

    /** The running sequence portion of a fund-based tracking code (e.g. 221-2026-06-8 → "8"). */
    public function dvNumber(): string
    {
        $parts = explode('-', (string) $this->tracking_code);

        return isset($parts[3]) ? preg_replace('/\D/', '', $parts[3]) : '';
    }

    public function isVoucher(): bool
    {
        return strtolower($this->document_type) === 'voucher';
    }

    /** "Transmittal of Letter · 12 documents" — null when this isn't a transmittal. */
    public function transmittalLabel(): ?string
    {
        if (! $this->is_transmittal) {
            return null;
        }

        return 'Transmittal of '.$this->document_type.' · '.$this->transmittal_quantity.' '.\Illuminate\Support\Str::plural('document', (int) $this->transmittal_quantity);
    }

    /** When did the document last change hands / status? */
    public function lastActionAt(): ?\Illuminate\Support\Carbon
    {
        return $this->updated_at;
    }

    /**
     * 'calendar_days' when this document's own department has opted into plain
     * calendar-day tracking for its internal view (departments.time_tracking_mode);
     * otherwise 'working_hours' (the shared engine). Driven by the document's
     * CURRENT department, so a future cross-office move naturally switches the
     * display back to working-hours for a department that hasn't opted in.
     */
    /**
     * This document's own tracking mode — an explicit per-document choice
     * (`documents.time_tracking_mode`) wins when set; otherwise it falls back to
     * its department's default. A department turning calendar-days ON only makes
     * the choice available to its documents; it doesn't force every one of them.
     */
    public function timeTrackingMode(): string
    {
        if ($this->time_tracking_mode) {
            return $this->time_tracking_mode;
        }

        return $this->department?->time_tracking_mode === 'calendar_days' ? 'calendar_days' : 'working_hours';
    }

    /** Whether weekends count, for a document in calendar-days mode — document override wins, else the department's setting. */
    public function calendarDaysIncludesWeekends(): bool
    {
        if ($this->calendar_days_include_weekends !== null) {
            return (bool) $this->calendar_days_include_weekends;
        }

        return $this->department?->calendar_days_include_weekends ?? true;
    }

    /**
     * Elapsed seconds between two instants for THIS document — calendar time when
     * this document (or its department, as a fallback) uses calendar-day tracking,
     * otherwise the shared working-hours engine (unaffected for every other document).
     */
    public function elapsedSeconds($start, $end = null, ?User $possessor = null): int
    {
        if (! $start) {
            return 0;
        }
        if ($this->timeTrackingMode() === 'calendar_days') {
            $start = \Illuminate\Support\Carbon::parse($start);
            $end = $end ? \Illuminate\Support\Carbon::parse($end) : now();
            if ($end->lessThanOrEqualTo($start)) {
                return 0;
            }

            return $this->calendarDaysIncludesWeekends()
                ? (int) $start->diffInSeconds($end)
                : static::weekdaySeconds($start, $end);
        }

        return \App\Services\BusinessHours::secondsBetween($start, $end, $possessor);
    }

    /** Full 24h per weekday between two instants, Saturday/Sunday excluded entirely. */
    protected static function weekdaySeconds(\Illuminate\Support\Carbon $start, \Illuminate\Support\Carbon $end): int
    {
        $seconds = 0;
        $cursor = $start->copy()->startOfDay();
        $guard = 0;
        while ($cursor->lessThan($end) && $guard++ < 3660) {
            if (! in_array($cursor->isoWeekday(), [6, 7], true)) {
                $dayStart = $cursor->copy();
                $dayEnd = $cursor->copy()->addDay();
                $s = $start->greaterThan($dayStart) ? $start : $dayStart;
                $e = $end->lessThan($dayEnd) ? $end : $dayEnd;
                if ($e->greaterThan($s)) {
                    $seconds += $s->diffInSeconds($e);
                }
            }
            $cursor->addDay();
        }

        return $seconds;
    }

    /** Human elapsed time since the last action, WITHOUT an "ago" suffix. */
    public function elapsedSinceLastAction(): string
    {
        if (! $this->lastActionAt()) {
            return '—';
        }

        return static::humanDuration(
            $this->elapsedSeconds($this->lastActionAt(), now(), $this->currentPossessor())
        );
    }

    /** Total turnaround for a finished document (received -> completed). */
    public function turnaround(): ?string
    {
        if ($this->received_at && $this->completed_at) {
            return static::humanDuration($this->elapsedSeconds($this->received_at, $this->completed_at));
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

    /**
     * Overdue state for the office's tracked document types, measured in working time.
     * Returns 'overdue' (past the limit), 'warning' (within 2 working days of it), or null.
     * The office sets its own limit (working days) and which types it tracks.
     */
    public function overdueState(): ?string
    {
        if ($this->isClosed()) {
            return null;
        }
        $dept = $this->department;
        if (! $dept || ! $dept->sla_enabled || ! $dept->sla_days) {
            return null;
        }
        $tracked = $dept->sla_document_type ?? [];
        if (! empty($tracked) && ! in_array($this->document_type, $tracked, true)) {
            return null;
        }

        $age = \App\Services\BusinessHours::secondsBetween($this->received_at ?? $this->created_at, now());
        $dayLen = \App\Services\BusinessHours::enabled()
            ? \App\Services\BusinessHours::dailyCapacitySeconds()
            : 86400; // wall-clock days when the working-hours engine is off
        $limit = $dept->sla_days * $dayLen;
        $warnAt = max(0, $dept->sla_days - 2) * $dayLen;

        if ($age >= $limit) {
            return 'overdue';
        }
        if ($age >= $warnAt) {
            return 'warning';
        }

        return null;
    }

    /* ----------------------------------------------------------------
     | Deadline (optional per-office, per-type due date)
     * ---------------------------------------------------------------- */

    /** The concrete due instant: end of the configured working day on the deadline date. */
    public function deadlineAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->deadline) {
            return null;
        }

        return $this->deadline->copy()->setTimeFromTimeString(\App\Models\Setting::get('work_end', '17:00'));
    }

    /** Default global rules, seeded the first time nothing has been configured yet. */
    public static function defaultDeadlineRules(): array
    {
        return [
            ['days' => 1, 'color' => '#f43f5e'],
            ['days' => 2, 'color' => '#f97316'],
        ];
    }

    public static function defaultDeadlineOverdueColor(): string
    {
        return '#dc2626';
    }

    /** Zip parallel "days" / "hex color" form arrays into the rules shape, dropping incomplete rows. */
    public static function zipDeadlineRules(array $days, array $colors): array
    {
        $rules = [];
        foreach ($days as $i => $d) {
            if ($d !== null && $d !== '' && ! empty($colors[$i])) {
                $rules[] = ['days' => (float) $d, 'color' => $colors[$i]];
            }
        }

        return $rules;
    }

    /**
     * Row-highlight for a still-open document with a deadline: an overdue color,
     * or the color of the nearest "X days before" rule reached — using this
     * document's OWN office's custom rules if it has any (deadline_enabled +
     * customized), otherwise the Super-Admin global defaults. Distance is
     * measured in working time remaining until 5 PM on the due date, converted
     * to whole working "days" using the office's configured day length so the
     * numbers mean what an admin typed ("3 days before").
     * Returns ['color' => '#hex', 'label' => string] or null.
     */
    public function deadlineHighlight(): ?array
    {
        $due = $this->deadlineAt();
        if (! $due || $this->isClosed()) {
            return null;
        }

        $dept = $this->department;
        $useDeptRules = $dept && $dept->deadline_enabled && ! empty($dept->deadline_highlight_rules);
        $rules = $useDeptRules
            ? $dept->deadline_highlight_rules
            : (json_decode((string) \App\Models\Setting::get('deadline_highlight_rules', ''), true) ?: static::defaultDeadlineRules());
        $overdueColor = ($dept && $dept->deadline_enabled && $dept->deadline_overdue_color)
            ? $dept->deadline_overdue_color
            : (\App\Models\Setting::get('deadline_overdue_color') ?: static::defaultDeadlineOverdueColor());

        if (now()->greaterThanOrEqualTo($due)) {
            return ['color' => $overdueColor, 'label' => 'Overdue'];
        }

        $remaining = \App\Services\BusinessHours::secondsBetween(now(), $due);
        $dayLen = \App\Services\BusinessHours::dailyCapacitySeconds();

        // Ascending by days so the tightest (nearest-due, most urgent) matching rule wins.
        foreach (collect($rules)->sortBy('days') as $rule) {
            if ($remaining <= (float) $rule['days'] * $dayLen) {
                return ['color' => $rule['color'], 'label' => rtrim(rtrim(number_format((float) $rule['days'], 1), '0'), '.').' day(s) before'];
            }
        }

        return null;
    }

    /* ----------------------------------------------------------------
     | Possession-based timing (who holds it & for how long)
     * ---------------------------------------------------------------- */

    /** Working seconds the current holder has had the document (0 when paused). */
    public function secondsWithCurrentHolder(): int
    {
        if ($this->is_pending || ! $this->possession_started_at) {
            return 0;
        }

        return $this->elapsedSeconds($this->possession_started_at, now(), $this->currentPossessor());
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
     * Total time the document spent paused/pending. The possession ledger has an
     * open segment at all times EXCEPT while pending, so the gap between the total
     * lifetime and the sum of all possession segments is the paused time.
     */
    public function totalPausedSeconds(): int
    {
        $held = $this->possessions->sum(fn ($s) => $s->seconds());

        return max(0, $this->totalSeconds() - (int) $held);
    }

    public function totalPausedTime(): string
    {
        return static::humanDuration($this->totalPausedSeconds());
    }

    /**
     * Combined time each user held this document, summed across every segment
     * they ever held it (a document can bounce back to the same person more
     * than once). Honors this document's own time-tracking mode (working hours,
     * or calendar days with its own weekend choice) for every segment — the
     * same rules used everywhere else on the document.
     */
    public function userHoldingSummary(): \Illuminate\Support\Collection
    {
        $totals = [];
        foreach ($this->possessions as $seg) {
            if (! $seg->holder_id) {
                continue; // office-pool segment, no specific person
            }
            $end = $seg->ended_at ?? ($this->isClosed() ? $this->completed_at : now());
            $totals[$seg->holder_id] = ($totals[$seg->holder_id] ?? 0)
                + $this->elapsedSeconds($seg->started_at, $end, $seg->holder);
        }

        return collect($totals)
            ->map(fn ($seconds, $userId) => ['user' => $this->possessions->firstWhere('holder_id', $userId)?->holder, 'seconds' => $seconds])
            ->filter(fn ($row) => $row['user'] !== null)
            ->sortByDesc('seconds')
            ->values();
    }

    /**
     * Format a number of seconds into a compact, human duration:
     *   "45 mins", "3 hrs 10 mins", "2 days 4 hrs".
     */
    public static function humanDuration(int|float|null $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '—';
        }
        $seconds = (int) round($seconds);
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
