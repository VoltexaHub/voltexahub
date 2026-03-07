<?php

use App\Plugins\Plugin;

class GithubSponsorsPlugin extends Plugin
{
    public function slug(): string
    {
        return 'github-sponsors';
    }

    public function name(): string
    {
        return 'GitHub Sponsors';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Integrate GitHub Sponsors with your forum.';
    }

    public function author(): string
    {
        return 'VoltexaHub';
    }

    public function register(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('plugins/github-sponsors/routes.php'));
    }

    public function boot(): void {}
}
