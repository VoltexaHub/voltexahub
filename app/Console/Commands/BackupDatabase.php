<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Create a compressed MySQL backup and prune backups older than 7 days';

    public function handle(): int
    {
        $disk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('backups'),
        ]);

        // Ensure directory exists
        if (!is_dir(storage_path('backups'))) {
            mkdir(storage_path('backups'), 0755, true);
        }

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $filename = 'voltexahub-backup-' . date('Y-m-d-His') . '.sql.gz';
        $filepath = storage_path('backups/' . $filename);

        $cmd = sprintf(
            'mysqldump -h%s -P%s -u%s -p%s %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => false]);

        if (!is_resource($process)) {
            Log::error('BackupDatabase: Failed to start mysqldump process');
            $this->error('Failed to start mysqldump process.');
            return self::FAILURE;
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
            Log::error('BackupDatabase: mysqldump failed', ['errors' => $errors, 'exit_code' => $exitCode]);
            $this->error('Backup failed: ' . $errors);
            // Clean up empty/failed file
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            return self::FAILURE;
        }

        $size = $this->formatBytes(filesize($filepath));
        Log::info("BackupDatabase: Created {$filename} ({$size})");
        $this->info("Backup created: {$filename} ({$size})");

        // Prune backups older than 7 days
        $this->pruneOldBackups();

        return self::SUCCESS;
    }

    protected function pruneOldBackups(): void
    {
        $backupDir = storage_path('backups');
        $cutoff = Carbon::now()->subDays(7);
        $pruned = 0;

        foreach (glob($backupDir . '/voltexahub-backup-*.sql.gz') as $file) {
            if (Carbon::createFromTimestamp(filemtime($file))->lt($cutoff)) {
                unlink($file);
                $pruned++;
            }
        }

        if ($pruned > 0) {
            Log::info("BackupDatabase: Pruned {$pruned} old backup(s)");
            $this->info("Pruned {$pruned} old backup(s).");
        }
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
