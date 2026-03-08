<?php

use App\Plugins\Plugin;

class BugReportsPlugin extends Plugin
{
    public function slug(): string { return 'bug-reports'; }
    public function name(): string { return 'Bug Reports'; }
    public function version(): string { return '1.0.0'; }
    public function description(): string { return 'Let users submit bug reports with screenshots and reproduction steps. Staff can track, triage, and resolve issues.'; }
    public function author(): string { return 'VoltexaHub'; }

    public function register(): void
    {
        require_once __DIR__ . '/models/BugReport.php';
        require_once __DIR__ . '/controllers/BugReportController.php';
        require_once __DIR__ . '/controllers/StaffBugReportController.php';

        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('plugins/bug-reports/routes.php'));
    }

    public function boot(): void {}
}
