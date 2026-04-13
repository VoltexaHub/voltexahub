<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;

class ForumIndexController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->orderBy('position')
            ->with([
                'forums' => fn ($q) => $q->orderBy('position'),
                'forums.lastPost.author:id,name',
                'forums.lastPost.thread:id,title,slug,forum_id',
            ])
            ->get();

        return view('theme::forum-index', compact('categories'));
    }
}
