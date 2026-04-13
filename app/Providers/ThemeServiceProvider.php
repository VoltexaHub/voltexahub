<?php

namespace App\Providers;

use App\Services\ThemeManager;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('voltexahub.php'), 'voltexahub');

        $this->app->singleton(ThemeManager::class, function ($app) {
            return new ThemeManager(
                themesPath: config('voltexahub.themes_path'),
                activeSlug: config('voltexahub.active_theme'),
            );
        });
    }

    public function boot(ThemeManager $themes): void
    {
        $themes->registerViews();

        view()->share('activeTheme', $themes->manifest());
    }
}
