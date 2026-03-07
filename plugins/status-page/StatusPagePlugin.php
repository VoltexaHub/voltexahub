<?php

use App\Plugins\Plugin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;

class StatusPagePlugin extends Plugin
{
    public function slug(): string
    {
        return 'status-page';
    }

    public function name(): string
    {
        return 'Status Page';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Real-time system status indicator showing forum health.';
    }

    public function author(): string
    {
        return 'VoltexaHub';
    }

    public function register(): void
    {
        Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('plugins/status-page/routes.php'));

        Route::middleware(['web'])
            ->group(base_path('plugins/status-page/routes_web.php'));
    }

    public function boot(): void
    {
        $this->registerSchedule();
    }

    protected function registerSchedule(): void
    {
        app()->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('status:check')->everyFiveMinutes();
        });
    }
}
