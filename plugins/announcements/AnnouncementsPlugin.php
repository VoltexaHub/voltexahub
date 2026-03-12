<?php

use App\Plugins\BasePlugin;
use App\Plugins\PluginHook;

class AnnouncementsPlugin extends BasePlugin
{
    protected function getSlug(): string
    {
        return 'announcements';
    }

    public function boot(): void
    {
        // Example hook: fire when thread is created in announcement forums
        PluginHook::on('thread.created', function ($thread) {
            // Announcements logic if needed
        });
    }

    public function register(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('plugins/announcements/routes.php'));
    }

    public function install(): void
    {
        $this->runMigrations();
    }

    public function uninstall(): void
    {
        $this->rollbackMigrations();
    }
}
