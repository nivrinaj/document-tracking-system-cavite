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
            'types' => DocumentType::with('department')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('document_types.create', ['departments' => Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        DocumentType::create($this->validateData($request));

        return redirect()->route('document-types.index')->with('success', 'Document type created.');
    }

    public function edit(DocumentType $documentType)
    {
        return view('document_types.edit', [
            'type' => $documentType,
            'departments' => Department::orderBy('name')->get(),
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
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'requires_voucher' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'requires_voucher' => $request->boolean('requires_voucher'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
