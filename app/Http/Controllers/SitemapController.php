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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Home
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . e($frontendUrl) . "/</loc>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";

        // Forums
        foreach ($forums as $forum) {
            $lastmod = $forum->updated_at?->toW3cString() ?? now()->toW3cString();
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . e($frontendUrl) . "/forum/" . e($forum->slug) . "</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        // Threads
        foreach ($threads as $thread) {
            $slug = $thread->slug ?? $thread->id;
            $lastmod = $thread->updated_at?->toW3cString() ?? $thread->created_at->toW3cString();
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . e($frontendUrl) . "/thread/" . e($slug) . "</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }
}
