<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /messages',
            'Disallow: /notifications',
            'Disallow: /bookmarks',
            'Disallow: /blocks',
            'Disallow: /profile',
            'Disallow: /search',
            'Disallow: /auth/',
            'Disallow: /mentions/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
