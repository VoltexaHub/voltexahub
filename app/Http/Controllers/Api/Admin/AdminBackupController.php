<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Restore from an uploaded .sql.gz file.
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:gz', 'max:51200'],
        ]);

        $file = $request->file('backup_file');

        return $this->performRestore($file->getRealPath());
    }

    /**
     * Restore from an existing server-side backup.
     */
    public function restoreFromBackup(string $filename): JsonResponse
    {
        if (!$this->isValidFilename($filename)) {
            return response()->json(['message' => 'Invalid filename.'], 400);
        }

        $path = $this->backupPath($filename);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

        return $this->performRestore($path);
    }

    protected function performRestore(string $filePath): JsonResponse
    {
        try {
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $cmd = sprintf(
                'gunzip -c %s | mysql -h%s -P%s -u%s -p%s %s',
                escapeshellarg($filePath),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database)
            );

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($cmd, $descriptors, $pipes);

            if (!is_resource($process)) {
                return response()->json(['message' => 'Failed to start restore process'], 500);
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                return response()->json(['message' => 'Database restore failed: ' . $errors], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully. Please refresh the page.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Database restore failed: ' . $e->getMessage()], 500);
        }
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
