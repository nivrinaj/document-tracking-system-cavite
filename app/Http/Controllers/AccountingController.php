<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use App\Models\NatureOfTransaction;
use App\Models\ResponsibilityCenter;
use App\Models\ResponsibilityCenterProject;
use App\Models\Setting;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index()
    {
        $dept = auth()->user()->department;

        return view('accounting.index', [
            'funds' => Fund::orderBy('sort_order')->orderBy('name')->get(),
            'centers' => ResponsibilityCenter::where('is_hospital', false)->with('projects')->orderBy('sort_order')->orderBy('name')->get(),
            'hospitalCenters' => ResponsibilityCenter::where('is_hospital', true)->orderBy('sort_order')->orderBy('name')->get(),
            'natures' => NatureOfTransaction::orderBy('sort_order')->orderBy('name')->get(),
            'department' => $dept,
            'trackableTypes' => $dept ? \App\Models\DocumentType::availableFor($dept->id)->pluck('name') : collect(),
            'rcHospitalRequired' => Setting::get('rc_hospital_required', '0') === '1',
        ]);
    }

    /** Super Admin toggle: whether Hospital-division encoders must pick a Responsibility Center. */
    public function updateHospitalRcRequired(Request $request)
    {
        $new = $request->boolean('rc_hospital_required') ? '1' : '0';
        $old = Setting::get('rc_hospital_required', '0');
        Setting::put('rc_hospital_required', $new);

        if ($old !== $new) {
            \App\Models\ActivityLog::record('accounting.hospital_rc_required', 'Hospital RC required '.($old === '1' ? 'ON' : 'OFF').' → '.($new === '1' ? 'ON' : 'OFF'));
        }

        return back()->with('success', 'Setting updated.');
    }

    /* ---------------- Overdue tracking (this office) ---------------- */
    public function updateOverdue(Request $request)
    {
        $dept = $request->user()->department;
        abort_unless($dept, 403, 'You are not assigned to an office.');

        $data = $request->validate([
            'sla_enabled' => ['nullable', 'boolean'],
            'sla_days' => ['nullable', 'required_if:sla_enabled,1', 'integer', 'min:1', 'max:365'],
            'sla_document_type' => ['nullable', 'array'],
            'sla_document_type.*' => ['string', 'max:100'],
        ]);

        $dept->update([
            'sla_enabled' => $request->boolean('sla_enabled'),
            'sla_days' => $data['sla_days'] ?? $dept->sla_days,
            'sla_document_type' => $data['sla_document_type'] ?? [],
        ]);

        return back()->with('success', 'Overdue tracking updated.');
    }

    /* ---------------- Funds ---------------- */
    public function storeFund(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
            'report_code' => ['nullable', 'string', 'max:20'],
            'hospital_available' => ['nullable', 'boolean'],
        ]);
        $fund = Fund::create([
            'name' => $data['name'], 'code' => $data['code'], 'report_code' => $data['report_code'] ?? null,
            'hospital_available' => $request->boolean('hospital_available'),
            'sort_order' => Fund::max('sort_order') + 1,
        ]);
        \App\Models\ActivityLog::record('accounting.funds.store', "Added a fund: {$fund->name} ({$fund->code}, #{$fund->id})", $fund);

        return back()->with('success', 'Fund added.');
    }

    public function updateFund(Request $request, Fund $fund)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
            'report_code' => ['nullable', 'string', 'max:20'],
            'hospital_available' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $fund->update([
            'name' => $data['name'], 'code' => $data['code'], 'report_code' => $data['report_code'] ?? null,
            'hospital_available' => $request->boolean('hospital_available'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Fund updated.');
    }

    public function destroyFund(Fund $fund)
    {
        $fund->delete();

        return back()->with('success', 'Fund removed.');
    }

    /* ---------------- Responsibility Centers (Office / Unit, and Hospital RCs) ---------------- */
    public function storeCenter(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_hospital' => ['nullable', 'boolean'],
        ]);
        $center = ResponsibilityCenter::create($data + [
            'is_hospital' => $request->boolean('is_hospital'),
            'sort_order' => ResponsibilityCenter::max('sort_order') + 1,
        ]);
        \App\Models\ActivityLog::record('accounting.centers.store', "Added a responsibility center: {$center->label()} (#{$center->id})".($center->is_hospital ? ' [Hospital]' : ''), $center);

        return back()->with('success', 'Responsibility center added.');
    }

    public function updateCenter(Request $request, ResponsibilityCenter $center)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'is_hospital' => ['nullable', 'boolean'],
        ]);
        $center->update($data + [
            'is_active' => $request->boolean('is_active'),
            'is_hospital' => $request->boolean('is_hospital'),
        ]);

        return back()->with('success', 'Responsibility center updated.');
    }

    public function destroyCenter(ResponsibilityCenter $center)
    {
        $center->delete();

        return back()->with('success', 'Responsibility center removed.');
    }

    /* ---------------- Projects (children of an Office/Unit Responsibility Center) ---------------- */
    public function storeProject(Request $request, ResponsibilityCenter $center)
    {
        abort_if($center->is_hospital, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);
        $project = $center->projects()->create($data + ['sort_order' => $center->projects()->max('sort_order') + 1]);
        \App\Models\ActivityLog::record('accounting.centers.projects.store', "Added a project: {$project->label()} under {$center->label()} (#{$project->id})", $project);

        return back()->with('success', 'Project added.');
    }

    public function updateProject(Request $request, ResponsibilityCenterProject $project)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $project->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Project updated.');
    }

    public function destroyProject(ResponsibilityCenterProject $project)
    {
        $project->delete();

        return back()->with('success', 'Project removed.');
    }

    /* ---------------- Nature of Transaction ---------------- */
    public function storeNature(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'report_code' => ['nullable', 'string', 'max:20'],
        ]);
        $nature = NatureOfTransaction::create($data + ['sort_order' => NatureOfTransaction::max('sort_order') + 1]);
        \App\Models\ActivityLog::record('accounting.natures.store', "Added a nature of transaction: {$nature->name} (#{$nature->id})", $nature);

        return back()->with('success', 'Nature of transaction added.');
    }

    public function updateNature(Request $request, NatureOfTransaction $nature)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'report_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $nature->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Nature of transaction updated.');
    }

    public function destroyNature(NatureOfTransaction $nature)
    {
        $nature->delete();

        return back()->with('success', 'Nature of transaction removed.');
    }
}
