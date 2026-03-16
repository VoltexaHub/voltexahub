<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;

class ForumConfigController extends Controller
{
    public function index(): JsonResponse
    {
        $configs = ForumConfig::all()->pluck('value', 'key')->toArray();

        // Strip sensitive keys from public response
        $sensitiveKeys = ['turnstile_secret_key', 'mail_password', 'stripe_secret', 'paypal_secret', 'plisio_secret'];
        foreach ($sensitiveKeys as $key) {
            unset($configs[$key]);
        }

        // Add SEO fields with proper defaults
        $configs['seo_description'] = $configs['seo_description'] ?? '';
        $configs['seo_title_format'] = $configs['seo_title_format'] ?? '{page} | {site}';
        $configs['seo_og_image'] = $configs['seo_og_image'] ?? '';
        $configs['seo_twitter_handle'] = $configs['seo_twitter_handle'] ?? '';
        $configs['seo_noindex'] = $configs['seo_noindex'] ?? 'false';

        return response()->json([
            'data' => $configs,
        ]);
    }
}
