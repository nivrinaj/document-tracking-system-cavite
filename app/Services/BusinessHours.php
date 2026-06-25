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
            $secs = static::secondsForDay($cursor, $start, $end, $cfg, $user);
            if ($secs > 0) {
                $out[$cursor->format('Y-m-d')] = $secs;
            }
            $cursor->addDay();
        }

        return $out;
    }

    protected static function secondsForDay(Carbon $day, Carbon $rangeStart, Carbon $rangeEnd, array $cfg, ?User $user): int
    {
        if (! in_array((int) $day->isoWeekday(), $cfg['days'], true)) {
            return 0;
        }

        $ex = CalendarDay::resolve($day, $user);
        if ($ex['off']) {
            return 0;
        }

        $ws = $day->copy()->setTimeFromTimeString($cfg['start']);
        $we = $day->copy()->setTimeFromTimeString($cfg['end']);

        // Undertime caps the productive window to the hours actually worked.
        if ($ex['worked_minutes'] !== null) {
            $we = static::undertimeEnd($ws, $cfg, $ex['worked_minutes']);
        }
        if ($we->lessThanOrEqualTo($ws)) {
            return 0;
        }

        // Clip the working window to the requested range.
        $s = $rangeStart->greaterThan($ws) ? $rangeStart->copy() : $ws;
        $e = $rangeEnd->lessThan($we) ? $rangeEnd->copy() : $we;
        if ($e->lessThanOrEqualTo($s)) {
            return 0;
        }

        $seconds = $s->diffInSeconds($e);

        // Remove any lunch overlap.
        $ls = $day->copy()->setTimeFromTimeString($cfg['lunch_start']);
        $le = $day->copy()->setTimeFromTimeString($cfg['lunch_end']);
        $seconds -= static::overlapSeconds($s, $e, $ls, $le);

        return max(0, (int) $seconds);
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
