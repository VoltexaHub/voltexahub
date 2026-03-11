<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\ForumConfig;
use App\Models\Thread;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $enabled = ForumConfig::get('seo_sitemap_enabled', 'true');
        if ($enabled === 'false' || $enabled === false) {
            abort(404);
        }

        $frontendUrl = rtrim(env('FRONTEND_URL', env('APP_URL')), '/');

        $forums = Forum::where('is_active', true)
            ->where('noindex', false)
            ->whereNull('parent_forum_id')
            ->orderBy('display_order')
            ->get();

        $threads = Thread::whereHas('forum', function ($q) {
            $q->where('is_active', true)->where('noindex', false);
        })
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $content = view('sitemap', [
            'frontendUrl' => $frontendUrl,
            'forums' => $forums,
            'threads' => $threads,
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
