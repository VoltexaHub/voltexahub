<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Category;
use App\Settings\Models\Setting;
use Inertia\Inertia;

class ForumIndexController
{
    public function __invoke(): \Inertia\Response
    {
        $categories = Category::with([
            'forums' => fn($q) => $q->orderBy('display_order'),
            'forums.lastPost.user',
            'forums.lastPost.thread',
        ])->orderBy('display_order')->get();

        return Inertia::render('Forum/Index', [
            'categories' => $categories,
            'siteName' => Setting::get('site_name', 'VoltexaHub'),
        ]);
    }
}
