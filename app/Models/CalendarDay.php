<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarDay extends Model
{
    protected $fillable = [
        'date', 'type', 'department_id', 'user_id', 'worked_hours', 'label', 'reason', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'worked_hours' => 'decimal:2',
    ];

    public const TYPES = ['holiday', 'suspension', 'dept_dayoff', 'user_leave', 'user_undertime'];

    /** Global, everyone-off entries (holidays + work suspensions). */
    public const GLOBAL_TYPES = ['holiday', 'suspension'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'holiday' => 'Holiday',
            'suspension' => 'Work suspension',
            'dept_dayoff' => 'Department day-off',
            'user_leave' => 'Leave',
            'user_undertime' => 'Undertime',
            default => ucfirst($this->type),
        };
    }

    /** Per-request cache of all calendar rows keyed by Y-m-d. */
    protected static array $cache = [];

    protected static function rowsFor(string $date)
    {
        if (! array_key_exists($date, static::$cache)) {
            static::$cache[$date] = static::whereDate('date', $date)->get();
        }

        return static::$cache[$date];
    }

    /**
     * Resolve whether a given day is fully off (and/or capped by undertime) for an
     * optional user. Global holidays/suspensions affect everyone; dept day-offs and
     * per-user leave/undertime only apply when that user is supplied.
     *
     * @return array{off: bool, worked_minutes: ?int, reasons: array<int,string>}
     */
    public static function resolve(Carbon $day, ?User $user = null): array
    {
        $off = false;
        $workedMinutes = null;
        $reasons = [];

        foreach (static::rowsFor($day->toDateString()) as $row) {
            if (in_array($row->type, static::GLOBAL_TYPES, true)) {
                $off = true;
                $reasons[] = $row->typeLabel().($row->label ? ": {$row->label}" : '');

                continue;
            }
            if (! $user) {
                continue;
            }
            if ($row->type === 'dept_dayoff' && (int) $row->department_id === (int) $user->department_id) {
                $off = true;
                $reasons[] = 'Department day-off'.($row->reason ? ": {$row->reason}" : '');
            }
            if ($row->type === 'user_leave' && (int) $row->user_id === (int) $user->id) {
                $off = true;
                $reasons[] = 'Leave'.($row->reason ? ": {$row->reason}" : '');
            }
            if ($row->type === 'user_undertime' && (int) $row->user_id === (int) $user->id) {
                $workedMinutes = (int) round((float) $row->worked_hours * 60);
                $reasons[] = 'Undertime ('.rtrim(rtrim(number_format((float) $row->worked_hours, 2), '0'), '.').'h)'.($row->reason ? ": {$row->reason}" : '');
            }
        }

        return ['off' => $off, 'worked_minutes' => $workedMinutes, 'reasons' => $reasons];
    }

    public static function clearCache(): void
    {
        static::$cache = [];
    }
}
