<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ThemeManager
{
    private ?array $manifest = null;

    public function __construct(
        private readonly string $themesPath,
        private readonly string $activeSlug,
    ) {}

    public function activeSlug(): string
    {
        return $this->activeSlug;
    }

    public function activePath(): string
    {
        return $this->themesPath.DIRECTORY_SEPARATOR.$this->activeSlug;
    }

    public function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $file = $this->activePath().DIRECTORY_SEPARATOR.'theme.json';
        if (! File::exists($file)) {
            return $this->manifest = ['slug' => $this->activeSlug, 'name' => $this->activeSlug];
        }

        return $this->manifest = json_decode(File::get($file), true) ?? [];
    }

    public function all(): array
    {
        if (! File::isDirectory($this->themesPath)) {
            return [];
        }

        return collect(File::directories($this->themesPath))
            ->map(function (string $dir) {
                $manifest = File::exists($dir.'/theme.json')
                    ? json_decode(File::get($dir.'/theme.json'), true) ?? []
                    : [];

                return array_merge(['slug' => basename($dir), 'name' => basename($dir)], $manifest);
            })
            ->values()
            ->all();
    }

    public function registerViews(): void
    {
        $viewsPath = $this->activePath().DIRECTORY_SEPARATOR.'views';
        if (File::isDirectory($viewsPath)) {
            View::addNamespace('theme', $viewsPath);
        }
    }
}
