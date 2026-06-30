<?php

namespace App\Http\Controllers;

use App\Models\DocumentationPage;
use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    public function index(Request $request)
    {
        $pages = DocumentationPage::orderBy('category')->orderBy('sort_order')->orderBy('title')->get();
        $grouped = $pages->groupBy('category');

        $current = null;
        if ($slug = $request->input('page')) {
            $current = $pages->firstWhere('slug', $slug);
        }
        $current = $current ?? $pages->first();

        return view('documentation.index', compact('grouped', 'current'));
    }

    public function show(DocumentationPage $page)
    {
        return redirect()->route('documentation.index', ['page' => $page->slug]);
    }

    public function create()
    {
        return view('documentation.create', ['page' => new DocumentationPage()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $page = DocumentationPage::create($data);
        \App\Models\ActivityLog::record('documentation.store', "Created a help page: {$page->title} (#{$page->id})", $page);

        return redirect()->route('documentation.index')->with('success', 'Documentation page created.');
    }

    public function edit(DocumentationPage $page)
    {
        return view('documentation.edit', compact('page'));
    }

    public function update(Request $request, DocumentationPage $page)
    {
        $data = $this->validateData($request);
        $page->update($data);

        return redirect()->route('documentation.index', ['page' => $page->slug])->with('success', 'Documentation page updated.');
    }

    public function destroy(DocumentationPage $page)
    {
        $page->delete();

        return redirect()->route('documentation.index')->with('success', 'Documentation page deleted.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }
}
