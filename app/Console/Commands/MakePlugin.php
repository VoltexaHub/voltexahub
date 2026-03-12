<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePlugin extends Command
{
    protected $signature = 'make:plugin {slug : The plugin slug (e.g. my-plugin)}';

    protected $description = 'Scaffold a new plugin directory with boilerplate files';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $slug = Str::slug($slug);
        $className = Str::studly($slug) . 'Plugin';
        $name = Str::headline($slug);
        $pluginDir = base_path("plugins/{$slug}");

        if (File::isDirectory($pluginDir)) {
            $this->error("Plugin directory already exists: plugins/{$slug}");
            return 1;
        }

        File::makeDirectory($pluginDir, 0755, true);
        File::makeDirectory("{$pluginDir}/migrations", 0755, true);

        // plugin.json
        $manifest = json_encode([
            'name' => $name,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => '',
            'description' => '',
            'requires' => '0.7.0',
            'frontend' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        File::put("{$pluginDir}/plugin.json", $manifest . "\n");

        // Main plugin class
        $classContent = <<<PHP
<?php

use App\Plugins\BasePlugin;
use App\Plugins\PluginHook;

class {$className} extends BasePlugin
{
    protected function getSlug(): string
    {
        return '{$slug}';
    }

    public function boot(): void
    {
        require_once __DIR__ . '/routes.php';
    }

    public function install(): void
    {
        \$this->runMigrations();
    }

    public function uninstall(): void
    {
        \$this->rollbackMigrations();
    }
}
PHP;

        File::put("{$pluginDir}/{$className}.php", $classContent . "\n");

        // routes.php
        $routesContent = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

// Plugin routes go here
// Route::middleware(['api'])->prefix('api')->group(function () {
//     Route::get('/example', fn () => response()->json(['message' => 'Hello from plugin']));
// });
PHP;

        File::put("{$pluginDir}/routes.php", $routesContent . "\n");

        $this->info("Plugin scaffolded: plugins/{$slug}");
        $this->line("  - {$className}.php");
        $this->line("  - plugin.json");
        $this->line("  - routes.php");
        $this->line("  - migrations/");

        return 0;
    }
}
