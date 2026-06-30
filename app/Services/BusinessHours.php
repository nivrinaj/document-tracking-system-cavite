<?php

namespace App\Services;

use App\Models\CalendarDay;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

/**
 * Computes productive ("working") time between two instants, honouring the global
 * work schedule (e.g. 8:00–5:00 Mon–Fri minus a 12:00–1:00 lunch), public holidays,
 * work suspensions, department day-offs and per-user leave/undertime.
 *
 * When the feature is OFF (default), every method falls back to plain wall-clock
 * time, so existing behaviour is unchanged until a Super Admin enables it.
 */
class BusinessHours
{
    public static function enabled(): bool
    {
        return Setting::get('work_hours_enabled', '0') === '1';
    }

    public static function config(): array
    {
        return [
            'start' => Setting::get('work_start', '08:00'),
            'end' => Setting::get('work_end', '17:00'),
            'lunch_start' => Setting::get('work_lunch_start', '12:00'),
            'lunch_end' => Setting::get('work_lunch_end', '13:00'),
            'days' => array_values(array_filter(array_map('intval', explode(',', Setting::get('work_days', '1,2,3,4,5'))))),
        ];
    }

    /** Productive seconds in one full working day (e.g. 8h with a 1h lunch). */
    public static function dailyCapacitySeconds(): int
    {
        $cfg = static::config();
        $ws = Carbon::parse($cfg['start']);
        $we = Carbon::parse($cfg['end']);
        $ls = Carbon::parse($cfg['lunch_start']);
        $le = Carbon::parse($cfg['lunch_end']);
        $secs = $ws->diffInSeconds($we) - max(0, (int) $ls->diffInSeconds($le));

        return max(1, (int) $secs);
    }

    /** Productive seconds between two instants (wall-clock when the feature is off). */
    public static function secondsBetween($start, $end = null, ?User $user = null): int
    {
        if (! $start) {
            return 0;
        }
        $start = Carbon::parse($start);
        $end = $end ? Carbon::parse($end) : Carbon::now();
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }
        if (! static::enabled()) {
            return (int) $start->diffInSeconds($end);
        }

        return (int) array_sum(static::dailyBreakdown($start, $end, $user));
    }

    /**
     * Productive seconds grouped by calendar day: [ 'Y-m-d' => seconds ].
     * Always computes against the schedule (used for the per-document breakdown).
     */
    public static function dailyBreakdown($start, $end = null, ?User $user = null): array
    {
        return array_map(fn ($d) => $d['seconds'], static::dailyDetail($start, $end, $user));
    }

    /**
     * Per-day detail: [ 'Y-m-d' => ['seconds' => int, 'from' => Carbon, 'to' => Carbon,
     * 'day_start' => Carbon, 'day_end' => Carbon] ].
     * "from"/"to" are the actual working window the range covered that day (e.g. 8:00 AM
     * if it started at open, or mid-morning if picked up late; 5:00 PM at close, or earlier).
     * "day_start"/"day_end" are that day's full configured working window (e.g. 8:00 AM–5:00 PM),
     * used to position "from"/"to" proportionally within the day rather than from a flat origin.
     */
    public static function dailyDetail($start, $end = null, ?User $user = null): array
    {
        $start = Carbon::parse($start);
        $end = $end ? Carbon::parse($end) : Carbon::now();
        $cfg = static::config();
        $out = [];
        if ($end->lessThanOrEqualTo($start)) {
            return $out;
        }

        $cursor = $start->copy()->startOfDay();
        $guard = 0;
        while ($cursor->lessThanOrEqualTo($end) && $guard++ < 3660) {
            $info = static::dayDetail($cursor, $start, $end, $cfg, $user);
            if ($info && $info['seconds'] > 0) {
                $out[$cursor->format('Y-m-d')] = $info;
            }
            $cursor->addDay();
        }

        return $out;
    }

    protected static function dayDetail(Carbon $day, Carbon $rangeStart, Carbon $rangeEnd, array $cfg, ?User $user): ?array
    {
        if (! in_array((int) $day->isoWeekday(), $cfg['days'], true)) {
            return null;
        }

        $ex = CalendarDay::resolve($day, $user);
        if ($ex['off']) {
            return null;
        }

        $ws = $day->copy()->setTimeFromTimeString($cfg['start']);
        $we = $day->copy()->setTimeFromTimeString($cfg['end']);

        // Undertime caps the productive window to the hours actually worked.
        if ($ex['worked_minutes'] !== null) {
            $we = static::undertimeEnd($ws, $cfg, $ex['worked_minutes']);
        }
        if ($we->lessThanOrEqualTo($ws)) {
            return null;
        }

        // Clip the working window to the requested range.
        $s = $rangeStart->greaterThan($ws) ? $rangeStart->copy() : $ws;
        $e = $rangeEnd->lessThan($we) ? $rangeEnd->copy() : $we;
        if ($e->lessThanOrEqualTo($s)) {
            return null;
        }

        $seconds = $s->diffInSeconds($e);

        // Remove any lunch overlap.
        $ls = $day->copy()->setTimeFromTimeString($cfg['lunch_start']);
        $le = $day->copy()->setTimeFromTimeString($cfg['lunch_end']);
        $seconds -= static::overlapSeconds($s, $e, $ls, $le);

        return ['seconds' => max(0, (int) $seconds), 'from' => $s, 'to' => $e, 'day_start' => $ws, 'day_end' => $we];
    }

    /** End of a capped undertime window: start + worked minutes, pushed past lunch if crossed. */
    protected static function undertimeEnd(Carbon $ws, array $cfg, int $workedMinutes): Carbon
    {
        $end = $ws->copy()->addMinutes(max(0, $workedMinutes));
        $ls = $ws->copy()->setTimeFromTimeString($cfg['lunch_start']);
        $le = $ws->copy()->setTimeFromTimeString($cfg['lunch_end']);
        if ($end->greaterThan($ls)) {
            $end->addSeconds((int) $ls->diffInSeconds($le));
        }

        return $end;
    }

    protected static function overlapSeconds(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): int
    {
        $start = $aStart->greaterThan($bStart) ? $aStart : $bStart;
        $end = $aEnd->lessThan($bEnd) ? $aEnd : $bEnd;
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return (int) $start->diffInSeconds($end);
    }
}
