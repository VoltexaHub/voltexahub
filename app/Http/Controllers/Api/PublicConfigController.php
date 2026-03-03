<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;

class PublicConfigController extends Controller
{
    public function customCode(): JsonResponse
    {
        return response()->json([
            'data' => [
                'custom_css' => ForumConfig::get('custom_css', ''),
                'custom_js' => ForumConfig::get('custom_js', ''),
            ],
        ]);
    }
}
