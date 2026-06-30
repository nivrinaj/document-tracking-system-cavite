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
        return view('departments.create', ['documentTypes' => $this->typeNames()]);
    }

    public function store(Request $request)
    {
        $department = Department::create($this->validateData($request));
        $this->applyTypeRestriction($request, $department);
        \App\Models\ActivityLog::record('departments.store', "Created a department: {$department->name} ({$department->code}, #{$department->id})", $department);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        $department->load(['divisions' => fn ($q) => $q->withCount('users')->orderBy('name')]);

        return view('departments.edit', ['department' => $department, 'documentTypes' => $this->typeNames()]);
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validateData($request, $department));
        $this->applyTypeRestriction($request, $department);

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    /**
     * Ensure accounting types exist, then set which document types this office may
     * encode. Accounting offices are auto-limited to Voucher/Payroll; otherwise use
     * the chosen subset (none chosen = all types).
     */
    private function applyTypeRestriction(Request $request, Department $department): void
    {
        $department->syncAccountingTypes();
        if ($request->boolean('is_accounting')) {
            $department->update(['restricted_doc_types' => ['Voucher', 'Payroll']]);

            return;
        }
        $types = array_values(array_filter((array) $request->input('restricted_doc_types', [])));
        $department->update(['restricted_doc_types' => $types ?: null]);
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
            'is_accounting' => ['nullable', 'boolean'],
            'sla_enabled' => ['nullable', 'boolean'],
            'sla_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'sla_document_type' => ['nullable', 'array'],
            'sla_document_type.*' => ['string', 'max:100'],
            'restricted_doc_types' => ['nullable', 'array'],
            'restricted_doc_types.*' => ['string', 'max:100'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
            'is_accounting' => $request->boolean('is_accounting'),
            'sla_enabled' => $request->boolean('sla_enabled'),
        ];
    }

    /** Active document type names for the SLA multi-select. */
    private function typeNames()
    {
        return \App\Models\DocumentType::where('is_active', true)
            ->orderBy('name')->pluck('name')->unique()->values();
    }
}
