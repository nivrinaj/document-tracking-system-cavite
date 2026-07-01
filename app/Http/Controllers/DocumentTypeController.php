<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index()
    {
        return view('document_types.index', [
            'types' => DocumentType::orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('document_types.create', [
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $type = DocumentType::create($this->validateData($request));
        \App\Models\ActivityLog::record('document-types.store', "Added a document type: {$type->name} (#{$type->id})", $type);

        return redirect()->route('document-types.index')->with('success', 'Document type created.');
    }

    public function edit(DocumentType $documentType)
    {
        return view('document_types.edit', [
            'type' => $documentType,
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $documentType->update($this->validateData($request));

        return redirect()->route('document-types.index')->with('success', 'Document type updated.');
    }

    public function destroy(DocumentType $documentType)
    {
        $documentType->delete();

        return back()->with('success', 'Document type deleted.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'requires_voucher' => ['nullable', 'boolean'],
            'requires_deadline' => ['nullable', 'boolean'],
            'allows_transmittal' => ['nullable', 'boolean'],
            'transmittal_scope' => ['nullable', 'in:all,selected'],
            'transmittal_departments' => ['nullable', 'array'],
            'transmittal_departments.*' => ['integer', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'requires_voucher' => $request->boolean('requires_voucher'),
            'requires_deadline' => $request->boolean('requires_deadline'),
            'allows_transmittal' => $request->boolean('allows_transmittal'),
            'transmittal_scope' => $request->input('transmittal_scope') === 'selected' ? 'selected' : 'all',
            'transmittal_departments' => implode(',', $data['transmittal_departments'] ?? []),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
