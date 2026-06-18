<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="sm:col-span-2">
        <label class="label">Title <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $page->title) }}" class="input" required>
    </div>
    <div>
        <label class="label">Category</label>
        <input list="cats" name="category" value="{{ old('category', $page->category ?? 'General') }}" class="input">
        <datalist id="cats"><option>Getting Started</option><option>User Guide</option><option>Developer Guide</option><option>Deployment</option><option>General</option></datalist>
    </div>
</div>
<div>
    <label class="label">Excerpt (short summary)</label>
    <input type="text" name="excerpt" value="{{ old('excerpt', $page->excerpt) }}" class="input">
</div>
<div>
    <label class="label">Content (Markdown supported) <span class="text-red-500">*</span></label>
    <textarea name="content" rows="18" class="input font-mono text-xs" required>{{ old('content', $page->content) }}</textarea>
    <p class="text-xs text-gray-400 mt-1">You can use Markdown: <code># Heading</code>, <code>**bold**</code>, <code>- lists</code>, <code>`code`</code>, tables, etc.</p>
</div>
<div class="max-w-[120px]">
    <label class="label">Sort order</label>
    <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" class="input">
</div>
