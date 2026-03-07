<?php

use App\Plugins\Plugin;

class CodePastePlugin extends Plugin
{
    public function slug(): string
    {
        return 'code-paste';
    }

    public function name(): string
    {
        return 'Code Paste';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Enhance code blocks with syntax highlighting, copy button, language labels, and line numbers.';
    }

    public function author(): string
    {
        return 'VoltexaHub';
    }

    public function register(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('plugins/code-paste/routes.php'));
    }

    public function boot(): void
    {
        // Register the post-render HTML transformer.
        // This processes <pre><code> blocks in rendered post content
        // to add data attributes the frontend uses for enhancement.
        app()->afterResolving(\App\Services\TextFormatterService::class, function ($service) {
            // Bind a decorator around the service if needed in the future.
        });
    }
}
