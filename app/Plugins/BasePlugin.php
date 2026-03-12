<?php

namespace App\Plugins;

use Illuminate\Support\Facades\Artisan;

abstract class BasePlugin
{
    protected string $slug;
    protected string $name;
    protected string $version;

    public function __construct()
    {
        $manifest = $this->getManifest();
        $this->slug = $manifest['slug'] ?? '';
        $this->name = $manifest['name'] ?? '';
        $this->version = $manifest['version'] ?? '1.0.0';
    }

    abstract public function boot(): void;

    public function install(): void {}
    public function uninstall(): void {}

    public function register(): void {}

    public function getManifest(): array
    {
        $path = base_path("plugins/{$this->getSlug()}/plugin.json");
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?? [];
    }

    abstract protected function getSlug(): string;

    public function slug(): string
    {
        return $this->getSlug();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function description(): string
    {
        return $this->getManifest()['description'] ?? '';
    }

    public function author(): string
    {
        return $this->getManifest()['author'] ?? '';
    }

    public function adminMenuItems(): array
    {
        return [];
    }

    public function runMigrations(): void
    {
        $migrationPath = base_path("plugins/{$this->getSlug()}/migrations");
        if (is_dir($migrationPath)) {
            Artisan::call('migrate', [
                '--path' => "plugins/{$this->getSlug()}/migrations",
                '--force' => true,
            ]);
        }
    }

    public function rollbackMigrations(): void
    {
        $migrationPath = base_path("plugins/{$this->getSlug()}/migrations");
        if (is_dir($migrationPath)) {
            Artisan::call('migrate:rollback', [
                '--path' => "plugins/{$this->getSlug()}/migrations",
                '--force' => true,
            ]);
        }
    }
}
