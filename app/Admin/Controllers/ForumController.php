<?php

namespace App\Admin\Controllers;

use App\Forum\Models\Category;
use App\Forum\Models\Forum;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ForumController
{
    public function index()
    {
        return Inertia::render('Admin/Forums/Index', [
            'forums' => Forum::with('category')->orderBy('display_order')->withCount('threads')->get(),
            'categories' => Category::orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);
        Forum::create($data + ['display_order' => (Forum::max('display_order') ?? 0) + 1]);
        return back()->with('success', 'Forum created.');
    }

    public function update(Request $request, Forum $forum)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);
        $forum->update($data);
        return back()->with('success', 'Forum updated.');
    }

    public function destroy(Forum $forum)
    {
        $forum->delete();
        return back()->with('success', 'Forum deleted.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);
        foreach ($data['order'] as $i => $id) {
            Forum::where('id', $id)->update(['display_order' => $i]);
        }
        return response()->json(['ok' => true]);
    }
}
