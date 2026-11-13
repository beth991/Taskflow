<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tags\StoreTagRequest;
use App\Http\Requests\Tags\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Tag::class);

        return view('tags.index', ['tags' => Tag::query()->withCount('tasks')->orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Tag::class);

        return view('tags.create', ['tag' => new Tag]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        Gate::authorize('create', Tag::class);
        Tag::query()->create($request->validated());

        return redirect()->route('admin.tags.index')->with('status', 'Tag created successfully.');
    }

    public function edit(Tag $tag): View
    {
        Gate::authorize('update', $tag);

        return view('tags.edit', compact('tag'));
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('update', $tag);
        $tag->update($request->validated());

        return redirect()->route('admin.tags.index')->with('status', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('status', 'Tag deleted successfully.');
    }
}
