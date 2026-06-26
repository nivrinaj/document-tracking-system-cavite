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
            'types' => DocumentType::with('departments')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('document_types.create', ['departments' => Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        [$data, $deptIds] = $this->validated($request);
        $type = DocumentType::create($data);
        $type->departments()->sync($data['availability'] === 'restricted' ? $deptIds : []);

        return redirect()->route('document-types.index')->with('success', 'Document type created.');
    }

    public function edit(DocumentType $documentType)
    {
        return view('document_types.edit', [
            'type' => $documentType->load('departments'),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        [$data, $deptIds] = $this->validated($request);
        $documentType->update($data);
        $documentType->departments()->sync($data['availability'] === 'restricted' ? $deptIds : []);

        return redirect()->route('document-types.index')->with('success', 'Document type updated.');
    }

    public function destroy(DocumentType $documentType)
    {
        $documentType->delete();

        return back()->with('success', 'Document type deleted.');
    }

    /** @return array{0: array<string,mixed>, 1: array<int>} validated attributes + selected department ids */
    private function validated(Request $request): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'availability' => ['required', 'in:all,restricted'],
            'departments' => ['nullable', 'array'],
            'departments.*' => ['integer', 'exists:departments,id'],
            'requires_voucher' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [[
            'name' => $request->input('name'),
            'availability' => $request->input('availability'),
            'requires_voucher' => $request->boolean('requires_voucher'),
            'is_active' => $request->boolean('is_active'),
        ], array_map('intval', $request->input('departments', []))];
    }
}
