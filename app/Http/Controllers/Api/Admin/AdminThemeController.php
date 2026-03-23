<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminThemeController extends Controller
{
    private function themesDir(): string
    {
        return storage_path('app/themes');
    }

    private function loadThemeMeta(string $slug): ?array
    {
        $jsonPath = $this->themesDir() . '/' . $slug . '/theme.json';
        if (!file_exists($jsonPath)) {
            return null;
        }
        $meta = json_decode(file_get_contents($jsonPath), true);
        return is_array($meta) ? $meta : null;
    }

    public function index(): JsonResponse
    {
        $activeTheme = ForumConfig::get('active_theme', 'default');
        $themesDir = $this->themesDir();
        $themes = [];

        if (is_dir($themesDir)) {
            foreach (scandir($themesDir) as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                $meta = $this->loadThemeMeta($dir);
                if (!$meta) continue;

                $themes[] = [
                    'id' => $meta['id'] ?? $dir,
                    'name' => $meta['name'] ?? $dir,
                    'version' => $meta['version'] ?? '1.0.0',
                    'author' => $meta['author'] ?? 'Unknown',
                    'description' => $meta['description'] ?? '',
                    'tags' => $meta['tags'] ?? [],
                    'mode' => $meta['mode'] ?? 'dark',
                    'active' => $activeTheme === ($meta['id'] ?? $dir),
                    'has_preview' => file_exists($themesDir . '/' . $dir . '/preview.png'),
                ];
            }
        }

        return response()->json([
            'data' => $themes,
            'active_theme' => $activeTheme,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:zip', 'max:5120'],
        ]);

        $uploadedFile = $request->file('file');

        $zip = new \ZipArchive();
        $tmpPath = $uploadedFile->getRealPath();

        if ($zip->open($tmpPath) !== true) {
            return response()->json(['message' => 'Invalid zip file.'], 422);
        }

        $jsonContent = $zip->getFromName('theme.json');
        if (!$jsonContent) {
            $zip->close();
            return response()->json(['message' => 'theme.json not found in zip.'], 422);
        }

        $meta = json_decode($jsonContent, true);
        if (!$meta || empty($meta['id']) || empty($meta['name'])) {
            $zip->close();
            return response()->json(['message' => 'Invalid theme.json — id and name are required.'], 422);
        }

        if ($zip->getFromName('theme.css') === false) {
            $zip->close();
            return response()->json(['message' => 'theme.css not found in zip.'], 422);
        }

        $slug = preg_replace('/[^a-z0-9\-_]/', '', strtolower($meta['id']));
        if ($slug === 'default') {
            $zip->close();
            return response()->json(['message' => 'Cannot override the default theme.'], 422);
        }

        $dest = $this->themesDir() . '/' . $slug;
        @mkdir($dest, 0755, true);
        $zip->extractTo($dest);
        $zip->close();

        return response()->json([
            'message' => 'Theme installed successfully.',
            'data' => [
                'id' => $slug,
                'name' => $meta['name'],
                'version' => $meta['version'] ?? '1.0.0',
                'author' => $meta['author'] ?? 'Unknown',
                'description' => $meta['description'] ?? '',
                'tags' => $meta['tags'] ?? [],
                'mode' => $meta['mode'] ?? 'dark',
                'active' => false,
                'has_preview' => file_exists($dest . '/preview.png'),
            ],
        ], 201);
    }

    public function activate(string $slug): JsonResponse
    {
        if ($slug !== 'default') {
            $meta = $this->loadThemeMeta($slug);
            if (!$meta) {
                return response()->json(['message' => 'Theme not found.'], 404);
            }
        }

        ForumConfig::set('active_theme', $slug);

        return response()->json(['message' => 'Theme activated.']);
    }

    public function destroy(string $slug): JsonResponse
    {
        if ($slug === 'default') {
            return response()->json(['message' => 'Cannot delete the default theme.'], 422);
        }

        $path = $this->themesDir() . '/' . $slug;
        if (!is_dir($path)) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        // Recursively delete theme directory
        $this->deleteDirectory($path);

        $active = ForumConfig::get('active_theme', 'default');
        if ($active === $slug) {
            ForumConfig::set('active_theme', 'default');
        }

        return response()->json(['message' => 'Theme removed.']);
    }

    public function preview(string $slug): Response
    {
        $previewPath = $this->themesDir() . '/' . $slug . '/preview.png';

        if (!file_exists($previewPath)) {
            abort(404, 'Preview not found.');
        }

        return response(file_get_contents($previewPath), 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function css(string $slug): Response
    {
        $cssPath = $this->themesDir() . '/' . $slug . '/theme.css';

        if (!file_exists($cssPath)) {
            abort(404, 'Theme CSS not found.');
        }

        return response(file_get_contents($cssPath), 200)
            ->header('Content-Type', 'text/css');
    }

    public function export(): Response
    {
        $customCss = ForumConfig::get('custom_css', '');

        $themeJson = json_encode([
            'id' => 'custom-export-' . time(),
            'name' => 'Custom Export',
            'version' => '1.0.0',
            'author' => 'VoltexaHub Admin',
            'description' => 'Exported custom CSS theme',
            'tags' => ['custom'],
            'mode' => 'dark',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $tmpFile = tempnam(sys_get_temp_dir(), 'theme_export_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', $themeJson);
        $zip->addFromString('theme.css', $customCss);
        $zip->close();

        $content = file_get_contents($tmpFile);
        @unlink($tmpFile);

        return response($content, 200)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="custom-theme-export.zip"');
    }

    public function activeCss(): Response
    {
        $activeTheme = ForumConfig::get('active_theme', 'default');

        if ($activeTheme === 'default') {
            return response('/* Default theme */', 200)->header('Content-Type', 'text/css');
        }

        $cssPath = $this->themesDir() . '/' . $activeTheme . '/theme.css';
        if (!file_exists($cssPath)) {
            return response('/* Theme CSS not found */', 200)->header('Content-Type', 'text/css');
        }

        $css = file_get_contents($cssPath);
        return response($css, 200)
            ->header('Content-Type', 'text/css')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
