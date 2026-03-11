<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSeoController extends Controller
{
    public function getSettings(): JsonResponse
    {
        return response()->json([
            'seo_description' => ForumConfig::get('seo_description', ''),
            'seo_title_format' => ForumConfig::get('seo_title_format', '{page} | {site}'),
            'seo_og_image' => ForumConfig::get('seo_og_image', ''),
            'seo_twitter_handle' => ForumConfig::get('seo_twitter_handle', ''),
            'seo_sitemap_enabled' => ForumConfig::get('seo_sitemap_enabled', 'true') === 'true',
            'seo_robots_txt' => ForumConfig::get('seo_robots_txt', "User-agent: *\nAllow: /"),
            'seo_noindex' => ForumConfig::get('seo_noindex', 'false') === 'true',
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'seo_description' => 'nullable|string|max:500',
            'seo_title_format' => 'nullable|string|max:255',
            'seo_og_image' => 'nullable|string|max:500',
            'seo_twitter_handle' => 'nullable|string|max:100',
            'seo_sitemap_enabled' => 'required|boolean',
            'seo_robots_txt' => 'nullable|string|max:2000',
            'seo_noindex' => 'required|boolean',
        ]);

        ForumConfig::set('seo_description', $request->input('seo_description', ''));
        ForumConfig::set('seo_title_format', $request->input('seo_title_format', '{page} | {site}'));
        ForumConfig::set('seo_og_image', $request->input('seo_og_image', ''));
        ForumConfig::set('seo_twitter_handle', $request->input('seo_twitter_handle', ''));
        ForumConfig::set('seo_sitemap_enabled', $request->boolean('seo_sitemap_enabled') ? 'true' : 'false');
        ForumConfig::set('seo_robots_txt', $request->input('seo_robots_txt', "User-agent: *\nAllow: /"));
        ForumConfig::set('seo_noindex', $request->boolean('seo_noindex') ? 'true' : 'false');

        return response()->json([
            'message' => 'SEO settings updated.',
        ]);
    }
}
