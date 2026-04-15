<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\Thread;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];

        $urls[] = ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'hourly'];

        foreach (Forum::orderBy('id')->get() as $forum) {
            $urls[] = [
                'loc' => route('forums.show', $forum->slug),
                'priority' => '0.8',
                'changefreq' => 'hourly',
                'lastmod' => $forum->last_post_at?->toIso8601String(),
            ];
        }

        Thread::query()
            ->with('forum:id,slug')
            ->orderByDesc('last_post_at')
            ->limit(5000)
            ->chunk(500, function ($threads) use (&$urls) {
                foreach ($threads as $thread) {
                    if (! $thread->forum) continue;
                    $urls[] = [
                        'loc' => route('threads.show', [$thread->forum->slug, $thread->slug]),
                        'priority' => '0.6',
                        'changefreq' => 'daily',
                        'lastmod' => ($thread->last_post_at ?? $thread->updated_at)?->toIso8601String(),
                    ];
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>".htmlspecialchars($u['loc']).'</loc>'."\n";
            if (! empty($u['lastmod'])) {
                $xml .= "    <lastmod>".htmlspecialchars($u['lastmod']).'</lastmod>'."\n";
            }
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
