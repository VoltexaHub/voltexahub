<?php

namespace App\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class PluginManager
{
    private array $plugins = [];
    private array $enabledSlugs = [];
    private bool $discovered = false;

    public function __construct(
        private readonly Application $app,
        private readonly string $pluginsPath,
        private readonly string $stateFile,
    ) {}

    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }
        $this->discovered = true;

        $this->enabledSlugs = $this->loadState();

        if (! File::isDirectory($this->pluginsPath)) {
            return;
        }

        foreach (File::directories($this->pluginsPath) as $dir) {
            $manifestPath = $dir.DIRECTORY_SEPARATOR.'plugin.json';
            if (! File::exists($manifestPath)) {
                continue;
            }
            $manifest = json_decode(File::get($manifestPath), true);
            if (! is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }
            $slug = $manifest['slug'];
            $this->plugins[$slug] = array_merge([
                'name' => $slug,
                'version' => '0.0.0',
                'description' => '',
                'author' => '',
            ], $manifest, [
                'slug' => $slug,
                'path' => $dir,
                'enabled' => in_array($slug, $this->enabledSlugs, true),
            ]);
        }
    }

    public function boot(HookManager $hooks): void
    {
        $this->discover();

        foreach ($this->plugins as $plugin) {
            if (! $plugin['enabled']) {
                continue;
            }

            $viewsPath = $plugin['path'].DIRECTORY_SEPARATOR.'views';
            if (File::isDirectory($viewsPath)) {
                View::addNamespace('plugin.'.$plugin['slug'], $viewsPath);
            }

            $migrationsPath = $plugin['path'].DIRECTORY_SEPARATOR.'migrations';
            if (File::isDirectory($migrationsPath)) {
                $this->app->make('migrator')->path($migrationsPath);
            }

            $routesFile = $plugin['path'].DIRECTORY_SEPARATOR.'routes.php';
            if (File::exists($routesFile)) {
                Route::middleware('web')->group($routesFile);
            }

            $bootstrap = $plugin['path'].DIRECTORY_SEPARATOR.'plugin.php';
            if (File::exists($bootstrap)) {
                (static function (string $file, Application $app, HookManager $hooks, array $plugin) {
                    require $file;
                })($bootstrap, $this->app, $hooks, $plugin);
            }
        }
    }

    public function all(): array
    {
        $this->discover();

        return array_values($this->plugins);
    }

    public function enable(string $slug): void
    {
        $this->discover();
        if (! isset($this->plugins[$slug])) {
            throw new \InvalidArgumentException("Plugin {$slug} not found.");
        }
        $enabled = array_unique(array_merge($this->enabledSlugs, [$slug]));
        $this->saveState($enabled);
    }

    public function disable(string $slug): void
    {
        $this->discover();
        $enabled = array_values(array_diff($this->enabledSlugs, [$slug]));
        $this->saveState($enabled);
    }

    private function loadState(): array
    {
        if (! File::exists($this->stateFile)) {
            return [];
        }
        $data = json_decode(File::get($this->stateFile), true);

        return is_array($data['enabled'] ?? null) ? $data['enabled'] : [];
    }

    private function saveState(array $enabled): void
    {
        $dir = dirname($this->stateFile);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($this->stateFile, json_encode(['enabled' => array_values($enabled)], JSON_PRETTY_PRINT));
        $this->enabledSlugs = $enabled;
    }
}
