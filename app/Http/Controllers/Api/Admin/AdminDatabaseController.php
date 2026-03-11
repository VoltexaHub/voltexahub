<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDatabaseController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $filename = 'voltexahub-backup-' . date('Y-m-d-His') . '.sql.gz';

        return response()->stream(function () use ($host, $port, $database, $username, $password) {
            $cmd = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s',
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

            $dumpProcess = proc_open($cmd, $descriptors, $dumpPipes);

            if (!is_resource($dumpProcess)) {
                throw new \RuntimeException('Failed to start mysqldump process');
            }

            fclose($dumpPipes[0]);

            $gzDescriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $gzProcess = proc_open('gzip -c', $gzDescriptors, $gzPipes);

            if (!is_resource($gzProcess)) {
                proc_close($dumpProcess);
                throw new \RuntimeException('Failed to start gzip process');
            }

            // Read from mysqldump, write to gzip, output gzip result
            while (!feof($dumpPipes[1])) {
                $chunk = fread($dumpPipes[1], 8192);
                if ($chunk !== false && $chunk !== '') {
                    fwrite($gzPipes[0], $chunk);
                }
            }

            fclose($dumpPipes[1]);
            $dumpErrors = stream_get_contents($dumpPipes[2]);
            fclose($dumpPipes[2]);
            $dumpExit = proc_close($dumpProcess);

            fclose($gzPipes[0]);

            while (!feof($gzPipes[1])) {
                $chunk = fread($gzPipes[1], 8192);
                if ($chunk !== false && $chunk !== '') {
                    echo $chunk;
                    flush();
                }
            }

            fclose($gzPipes[1]);
            fclose($gzPipes[2]);
            proc_close($gzProcess);

            if ($dumpExit !== 0) {
                throw new \RuntimeException('mysqldump failed: ' . $dumpErrors);
            }
        }, 200, [
            'Content-Type' => 'application/gzip',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:gz', 'max:51200'],
        ]);

        try {
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $file = $request->file('backup_file');

            $cmd = sprintf(
                'gunzip -c %s | mysql -h%s -P%s -u%s -p%s %s',
                escapeshellarg($file->getRealPath()),
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
}
