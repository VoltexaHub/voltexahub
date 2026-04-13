<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Forum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Forums/Index', [
            'forums' => Forum::with('category:id,name')->orderBy('category_id')->orderBy('position')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Forums/Form', [
            'forum' => null,
            'categories' => Category::orderBy('position')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] ??= Str::slug($data['name']);
        $data['position'] ??= (Forum::where('category_id', $data['category_id'])->max('position') ?? -1) + 1;

        Forum::create($data);

        return redirect()->route('admin.forums.index')->with('flash.success', 'Forum created.');
    }

    public function edit(Forum $forum): Response
    {
        return Inertia::render('Admin/Forums/Form', [
            'forum' => $forum,
            'categories' => Category::orderBy('position')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Forum $forum): RedirectResponse
    {
        $data = $this->validated($request, $forum);
        $forum->update($data);

        return redirect()->route('admin.forums.index')->with('flash.success', 'Forum updated.');
    }

    public function destroy(Forum $forum): RedirectResponse
    {
        $forum->delete();

        return redirect()->route('admin.forums.index')->with('flash.success', 'Forum deleted.');
    }

    private function validated(Request $request, ?Forum $forum = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('forums', 'slug')->ignore($forum?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
