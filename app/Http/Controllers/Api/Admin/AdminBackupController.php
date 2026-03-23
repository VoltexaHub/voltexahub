<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminBackupController extends Controller
{
    protected function backupPath(string $filename = ''): string
    {
        return storage_path('backups' . ($filename ? '/' . $filename : ''));
    }

    public function index(): JsonResponse
    {
        $dir = $this->backupPath();

        if (!is_dir($dir)) {
            return response()->json(['data' => []]);
        }

        $files = glob($dir . '/voltexahub-backup-*.sql.gz');
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'size_human' => $this->formatBytes(filesize($file)),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // Sort newest first
        usort($backups, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return response()->json(['data' => $backups]);
    }

    public function create(): JsonResponse
    {
        $exitCode = Artisan::call('backup:database');
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $output,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $output,
        ]);
    }

    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        if (!$this->isValidFilename($filename)) {
            return response()->json(['message' => 'Invalid filename.'], 400);
        }

        $path = $this->backupPath($filename);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    public function destroy(string $filename): JsonResponse
    {
        if (!$this->isValidFilename($filename)) {
            return response()->json(['message' => 'Invalid filename.'], 400);
        }

        $path = $this->backupPath($filename);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

        unlink($path);

        return response()->json([
            'success' => true,
            'message' => 'Backup deleted.',
        ]);
    }

    /**
     * Validate filename to prevent path traversal — only allow our backup naming pattern.
     */
    protected function isValidFilename(string $filename): bool
    {
        return (bool) preg_match('/^voltexahub-backup-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz$/', $filename);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
