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
        return view('document_types.create');
    }

    public function store(Request $request)
    {
        $type = DocumentType::create($this->validateData($request));
        \App\Models\ActivityLog::record('document-types.store', "Added a document type: {$type->name} (#{$type->id})", $type);

        return redirect()->route('document-types.index')->with('success', 'Document type created.');
    }

    public function edit(DocumentType $documentType)
    {
        return view('document_types.edit', ['type' => $documentType]);
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
            'requires_voucher' => ['nullable', 'boolean'],
            'requires_deadline' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'requires_voucher' => $request->boolean('requires_voucher'),
            'requires_deadline' => $request->boolean('requires_deadline'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
