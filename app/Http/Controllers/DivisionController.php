<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
{
    public function index()
    {
        return view('divisions.index', [
            'divisions' => Division::with('department')->withCount(['users', 'documents'])->orderBy('name')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('divisions.create', ['departments' => \App\Models\Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Division::create($data);

        return redirect()->route('divisions.index')->with('success', 'Division created.');
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

        return redirect()->route('divisions.index')->with('success', 'Division updated.');
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

    private function validateData(Request $request, ?Division $division = null): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')->ignore($division?->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
