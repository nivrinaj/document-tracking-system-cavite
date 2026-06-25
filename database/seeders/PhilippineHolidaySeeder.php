<?php

namespace Database\Seeders;

use App\Models\CalendarDay;
use Illuminate\Database\Seeder;

/**
 * Preloads the 2026 Philippine holidays. Regular + special non-working days are
 * all stored as "holiday" (zero working hours). Idempotent — safe to re-run.
 * Islamic holidays are movable and only confirmed by proclamation; included as
 * best estimates and flagged "(tentative)" so an admin can adjust the exact date.
 */
class PhilippineHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            // ── Regular holidays 2026 ──
            ['2026-01-01', "New Year's Day (Regular)"],
            ['2026-04-02', 'Maundy Thursday (Regular)'],
            ['2026-04-03', 'Good Friday (Regular)'],
            ['2026-04-09', 'Araw ng Kagitingan (Regular)'],
            ['2026-05-01', 'Labor Day (Regular)'],
            ['2026-06-12', 'Independence Day (Regular)'],
            ['2026-08-31', 'National Heroes Day (Regular)'],
            ['2026-11-30', 'Bonifacio Day (Regular)'],
            ['2026-12-25', 'Christmas Day (Regular)'],
            ['2026-12-30', 'Rizal Day (Regular)'],
            ['2026-03-20', "Eid'l Fitr (Regular — tentative)"],
            ['2026-05-27', "Eid'l Adha (Regular — tentative)"],

            // ── Special (non-working) days 2026 ──
            ['2026-02-17', 'Chinese New Year (Special non-working)'],
            ['2026-04-04', 'Black Saturday (Special non-working)'],
            ['2026-08-21', 'Ninoy Aquino Day (Special non-working)'],
            ['2026-11-01', "All Saints' Day (Special non-working)"],
            ['2026-12-08', 'Feast of the Immaculate Conception (Special non-working)'],
            ['2026-12-24', 'Christmas Eve (Special non-working)'],
            ['2026-12-31', 'Last Day of the Year (Special non-working)'],
        ];

        foreach ($holidays as [$date, $label]) {
            CalendarDay::firstOrCreate(
                ['date' => $date, 'type' => 'holiday', 'label' => $label],
            );
        }
    }
}
