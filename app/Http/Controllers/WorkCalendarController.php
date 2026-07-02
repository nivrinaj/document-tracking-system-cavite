<?php

namespace App\Http\Controllers;

use App\Models\CalendarDay;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkCalendarController extends Controller
{
    /* ───────────────── Work hours (Super Admin) ───────────────── */
    public function settings()
    {
        return view('work-calendar.settings', [
            'cfg' => \App\Services\BusinessHours::config(),
            'enabled' => \App\Services\BusinessHours::enabled(),
            'showBreakdown' => Setting::get('show_daily_breakdown', '1') === '1',
            'displayMode' => Setting::get('calendar_display', 'grid'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i', 'after:work_start'],
            'work_lunch_start' => ['required', 'date_format:H:i'],
            'work_lunch_end' => ['required', 'date_format:H:i', 'after:work_lunch_start'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'between:1,7'],
            'calendar_display' => ['required', Rule::in(['disabled', 'list', 'grid'])],
        ]);

        Setting::put('work_start', $data['work_start']);
        Setting::put('work_end', $data['work_end']);
        Setting::put('work_lunch_start', $data['work_lunch_start']);
        Setting::put('work_lunch_end', $data['work_lunch_end']);
        Setting::put('work_days', implode(',', $data['work_days']));
        Setting::put('calendar_display', $data['calendar_display']);
        Setting::put('work_hours_enabled', $request->boolean('work_hours_enabled') ? '1' : '0');
        Setting::put('show_daily_breakdown', $request->boolean('show_daily_breakdown') ? '1' : '0');

        return back()->with('success', 'Work-hours settings saved.');
    }

    /* ───────────────── Holidays & suspensions (Super Admin) ───────────────── */
    public function holidays(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        return view('work-calendar.holidays', [
            'year' => $year,
            'displayMode' => Setting::get('calendar_display', 'grid'),
            'days' => CalendarDay::whereIn('type', CalendarDay::GLOBAL_TYPES)
                ->whereYear('date', $year)->orderBy('date')->get(),
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(['holiday', 'suspension', 'other'])],
            'label' => ['required', 'string', 'max:150'],
        ]);
        $entry = CalendarDay::create($data + ['created_by' => $request->user()->id]);

        \App\Models\ActivityLog::record(
            'work-calendar.holidays.store',
            'Added a holiday: '.$data['label'].' on '.$data['date'],
            $entry,
        );

        return back()->with('success', 'Entry added.');
    }

    public function destroyHoliday(CalendarDay $calendarDay)
    {
        abort_unless(in_array($calendarDay->type, CalendarDay::GLOBAL_TYPES, true), 404);

        \App\Models\ActivityLog::record(
            'work-calendar.holidays.destroy',
            'Deleted a holiday: '.$calendarDay->label.' on '.$calendarDay->date->toDateString().' (#'.$calendarDay->id.')',
            $calendarDay,
        );

        $calendarDay->delete();

        return back()->with('success', 'Entry removed.');
    }

    /* ───────────────── Team calendar (calendar.manage) ───────────────── */
    public function team(Request $request)
    {
        $user = $request->user();
        $departments = \App\Models\Department::orderBy('name')->get();
        // Managers are locked to their own office; users without one (e.g. Super
        // Admin) may pick which office to manage.
        $canChoose = ! $user->department;
        $dept = $user->department
            ?? ($request->filled('department_id')
                ? $departments->firstWhere('id', (int) $request->department_id)
                : $departments->first());
        abort_unless($dept, 403, 'No departments exist yet — create one first.');

        $year = (int) $request->input('year', now()->year);

        return view('work-calendar.team', [
            'department' => $dept,
            'departments' => $departments,
            'canChoose' => $canChoose,
            'year' => $year,
            'displayMode' => Setting::get('calendar_display', 'grid'),
            'staffGroups' => $this->groupedStaff($dept),
            'days' => CalendarDay::whereIn('type', ['dept_dayoff', 'user_leave', 'user_undertime'])
                ->where(fn ($q) => $q->where('department_id', $dept->id)
                    ->orWhereIn('user_id', User::where('department_id', $dept->id)->pluck('id')))
                ->whereYear('date', $year)->with(['user', 'creator'])->orderBy('date')->get(),
        ]);
    }

    /**
     * Staff of an office grouped for a searchable dropdown:
     * Office heads first (Dept Head, then Assistant), then each division
     * (alphabetical) with its Division Head first, then staff (alphabetical).
     *
     * @return array<int,array{label:string,items:array<int,array{id:int,name:string}>}>
     */
    private function groupedStaff(\App\Models\Department $dept): array
    {
        $users = User::where('department_id', $dept->id)->with(['roles:id,name', 'division:id,code,name'])->get();
        $isHead = fn ($u) => $u->hasSystemRole(User::SYS_DEPARTMENT_HEAD);
        $isAsst = fn ($u) => $u->hasSystemRole(User::SYS_ASSISTANT_DEPARTMENT_HEAD);
        $isDivHead = fn ($u) => $u->hasSystemRole(User::SYS_DIVISION_HEAD);

        $groups = [];

        $heads = $users->filter(fn ($u) => $isHead($u) || $isAsst($u))
            ->sortBy(fn ($u) => ($isHead($u) ? '0' : '1').'_'.$u->name)->values();
        if ($heads->isNotEmpty()) {
            $groups[] = ['label' => 'Office heads', 'items' => $heads->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name.($isHead($u) ? ' · Dept Head' : ' · Asst. Head'),
            ])->all()];
        }

        $rest = $users->reject(fn ($u) => $isHead($u) || $isAsst($u));
        foreach ($rest->groupBy(fn ($u) => $u->division?->name ?? 'No division')->sortKeys() as $divName => $members) {
            $code = $members->first()->division?->code;
            $sorted = $members->sortBy(fn ($u) => ($isDivHead($u) ? '0' : '1').'_'.$u->name)->values();
            $groups[] = [
                'label' => $code ? "{$code} — {$divName}" : $divName,
                'items' => $sorted->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name.($isDivHead($u) ? ' · Div Head' : ''),
                ])->all(),
            ];
        }

        return $groups;
    }

    public function storeTeam(Request $request)
    {
        $user = $request->user();
        $dept = $user->department
            ?? \App\Models\Department::find($request->input('department_id'));
        abort_unless($dept, 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(['dept_dayoff', 'user_leave', 'user_undertime'])],
            'date' => ['required', 'date'],
            'user_id' => ['nullable', 'required_unless:type,dept_dayoff', Rule::exists('users', 'id')->where('department_id', $dept->id)],
            'worked_hours' => ['nullable', 'required_if:type,user_undertime', 'numeric', 'min:0', 'max:24'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $entry = CalendarDay::create([
            'type' => $data['type'],
            'date' => $data['date'],
            'department_id' => $data['type'] === 'dept_dayoff' ? $dept->id : null,
            'user_id' => $data['type'] === 'dept_dayoff' ? null : $data['user_id'],
            'worked_hours' => $data['type'] === 'user_undertime' ? $data['worked_hours'] : null,
            'reason' => $data['reason'],
            'created_by' => $request->user()->id,
        ]);

        // Accountability: log who set this exclusion and why.
        \App\Models\ActivityLog::record(
            'calendar.'.$data['type'],
            $entry->typeLabel().' on '.$data['date'].($entry->user ? ' for '.$entry->user->name : '')
                .($entry->worked_hours !== null ? ' ('.$entry->worked_hours.'h worked)' : '').' — '.$data['reason'],
            $entry,
            $request->user()->id,
        );

        return back()->with('success', $entry->typeLabel().' recorded.');
    }

    public function destroyTeam(Request $request, CalendarDay $calendarDay)
    {
        $dept = $request->user()->department;
        $isTeamType = in_array($calendarDay->type, ['dept_dayoff', 'user_leave', 'user_undertime'], true);
        // Managers may only remove their own office's entries; users without a fixed
        // office (Super Admin) may remove any team entry.
        $belongs = ! $dept
            || $calendarDay->department_id === $dept->id
            || ($calendarDay->user && $calendarDay->user->department_id === $dept->id);
        abort_unless($belongs && $isTeamType, 404);

        \App\Models\ActivityLog::record(
            'calendar.remove',
            'Removed '.$calendarDay->typeLabel().' on '.$calendarDay->date->toDateString(),
            $calendarDay,
            $request->user()->id,
        );
        $calendarDay->delete();

        return back()->with('success', 'Entry removed.');
    }
}
