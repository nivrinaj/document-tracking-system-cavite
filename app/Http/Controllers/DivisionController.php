<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
{
    public function index()
    {
        // Divisions are managed inside their Department now.
        return redirect()->route('departments.index');
    }

    public function create()
    {
        return view('divisions.create', ['departments' => \App\Models\Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $division = Division::create($data);
        \App\Models\ActivityLog::record('divisions.store', "Created a division: {$division->name} ({$division->code}, #{$division->id})", $division);

        return $this->redirectAfter($division)->with('success', 'Division created.');
    }

    public function edit(Division $division)
    {
        return view('divisions.edit', [
            'division' => $division,
            'departments' => \App\Models\Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Division $division)
    {
        $data = $this->validateData($request, $division);
        $division->update($data);

        return $this->redirectAfter($division)->with('success', 'Division updated.');
    }

    public function destroy(Division $division)
    {
        if ($division->users()->exists() || $division->documents()->exists()) {
            return back()->with('error', 'Cannot delete a division that still has users or documents.');
        }

        $division->delete();

        return back()->with('success', 'Division deleted.');
    }

    public function show(Division $division)
    {
        return redirect()->route('divisions.edit', $division);
    }

    /** Return to the parent department page when the division belongs to one. */
    private function redirectAfter(Division $division)
    {
        return $division->department_id
            ? redirect()->route('departments.edit', $division->department_id)
            : redirect()->route('departments.index');
    }

    private function validateData(Request $request, ?Division $division = null): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')->ignore($division?->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_hospital' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active'), 'is_hospital' => $request->boolean('is_hospital')];
    }
}
