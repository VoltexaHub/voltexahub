<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminThemeController extends Controller
{
    public function index(): JsonResponse
    {
        $activeTheme = ForumConfig::where('key', 'active_theme')->value('value') ?? 'default';
        $themesDir = storage_path('app/themes');
        $themes = [];

        $themes[] = [
            'id' => 'default',
            'name' => 'Default',
            'description' => 'The default VoltexaHub theme',
            'version' => '1.0.0',
            'author' => 'VoltexaHub',
            'preview' => ['accent' => '#7c3aed', 'bg' => '#030712'],
            'active' => $activeTheme === 'default',
            'built_in' => true,
        ];

        if (is_dir($themesDir)) {
            foreach (scandir($themesDir) as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                $jsonPath = $themesDir . '/' . $dir . '/theme.json';
                if (!file_exists($jsonPath)) continue;
                $meta = json_decode(file_get_contents($jsonPath), true);
                if (!$meta) continue;
                $meta['active'] = $activeTheme === ($meta['id'] ?? $dir);
                $meta['built_in'] = false;
                $themes[] = $meta;
            }
        }

        return response()->json(['data' => $themes]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:zip', 'max:2048'],
        ]);

        $uploadedFile = $request->file('file');
        if ($uploadedFile->getMimeType() !== 'application/zip') {
            return response()->json(['message' => 'Invalid file type. Only ZIP files are allowed.'], 422);
        }

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

        $slug = preg_replace('/[^a-z0-9\-_]/', '', strtolower($meta['id']));
        if ($slug === 'default') {
            $zip->close();
            return response()->json(['message' => 'Cannot override the default theme.'], 422);
        }

        if ($zip->getFromName('theme.css') === false) {
            $zip->close();
            return response()->json(['message' => 'theme.css not found in zip.'], 422);
        }

        $dest = storage_path("app/themes/{$slug}");
        @mkdir($dest, 0755, true);
        $zip->extractTo($dest);
        $zip->close();

        return response()->json([
            'message' => 'Theme installed successfully.',
            'data' => array_merge($meta, ['id' => $slug, 'active' => false, 'built_in' => false]),
        ], 201);
    }

    public function activate(string $slug): JsonResponse
    {
        if ($slug !== 'default') {
            $jsonPath = storage_path("app/themes/{$slug}/theme.json");
            if (!file_exists($jsonPath)) {
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

        $path = storage_path("app/themes/{$slug}");
        if (!is_dir($path)) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        array_map('unlink', glob("{$path}/*"));
        rmdir($path);

        $active = ForumConfig::where('key', 'active_theme')->value('value');
        if ($active === $slug) {
            ForumConfig::set('active_theme', 'default');
        }

        return response()->json(['message' => 'Theme removed.']);
    }

    public function activeCss(): \Illuminate\Http\Response
    {
        $activeTheme = ForumConfig::where('key', 'active_theme')->value('value') ?? 'default';

        if ($activeTheme === 'default') {
            return response('/* Default theme */', 200)->header('Content-Type', 'text/css');
        }

        $cssPath = storage_path("app/themes/{$activeTheme}/theme.css");
        if (!file_exists($cssPath)) {
            return response('/* Theme CSS not found */', 200)->header('Content-Type', 'text/css');
        }

        $css = file_get_contents($cssPath);
        return response($css, 200)
            ->header('Content-Type', 'text/css')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
