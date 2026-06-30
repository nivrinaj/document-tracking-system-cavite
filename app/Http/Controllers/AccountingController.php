<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use App\Models\NatureOfTransaction;
use App\Models\ResponsibilityCenter;
use App\Models\ResponsibilityCenterProject;
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
        ]);
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
        Fund::create([
            'name' => $data['name'], 'code' => $data['code'], 'report_code' => $data['report_code'] ?? null,
            'hospital_available' => $request->boolean('hospital_available'),
            'sort_order' => Fund::max('sort_order') + 1,
        ]);

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
        ResponsibilityCenter::create($data + [
            'is_hospital' => $request->boolean('is_hospital'),
            'sort_order' => ResponsibilityCenter::max('sort_order') + 1,
        ]);

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
        $center->projects()->create($data + ['sort_order' => $center->projects()->max('sort_order') + 1]);

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
        NatureOfTransaction::create($data + ['sort_order' => NatureOfTransaction::max('sort_order') + 1]);

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
