<?php

namespace App\Providers;

use App\Services\HookManager;
use App\Services\PluginManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HookManager::class);

        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                app: $app,
                pluginsPath: base_path('plugins'),
                stateFile: storage_path('app/plugins.json'),
            );
        });
    }

    public function boot(PluginManager $plugins, HookManager $hooks): void
    {
        $plugins->boot($hooks);

        Blade::directive('hook', function (string $expression) {
            return "<?php echo app(\\App\\Services\\HookManager::class)->emit({$expression}); ?>";
        });
    }
}
