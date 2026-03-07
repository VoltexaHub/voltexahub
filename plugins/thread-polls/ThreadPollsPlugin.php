<?php

use App\Plugins\Plugin;

class ThreadPollsPlugin extends Plugin
{
    public function slug(): string
    {
        return 'thread-polls';
    }

    public function name(): string
    {
        return 'Thread Polls';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Add polls to forum threads — create questions, vote, and view results.';
    }

    public function author(): string
    {
        return 'VoltexaHub';
    }

    public function register(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('plugins/thread-polls/routes.php'));
    }

    public function boot(): void {}
}
