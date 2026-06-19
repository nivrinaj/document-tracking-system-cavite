<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('departments.index', [
            'departments' => Department::withCount(['divisions', 'users', 'documents'])->orderBy('name')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('departments.create');
    }

    public function store(Request $request)
    {
        Department::create($this->validateData($request));

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validateData($request, $department));

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        if ($department->divisions()->exists() || $department->users()->exists() || $department->documents()->exists()) {
            return back()->with('error', 'Cannot delete a department that still has divisions, users or documents.');
        }

        $department->delete();

        return back()->with('success', 'Department deleted.');
    }

    public function show(Department $department)
    {
        return redirect()->route('departments.edit', $department);
    }

    private function validateData(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($department?->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
