<?php

namespace App\Http\Controllers;

use App\Models\ForumConfig;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $noindex = ForumConfig::get('seo_noindex', 'false');

        if ($noindex === 'true' || $noindex === true) {
            $content = "User-agent: *\nDisallow: /";
        } else {
            $content = ForumConfig::get('seo_robots_txt', "User-agent: *\nAllow: /");
        }

        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
