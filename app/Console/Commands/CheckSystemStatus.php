<?php

namespace App\Console\Commands;

use App\Models\StatusCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckSystemStatus extends Command
{
    protected $signature = 'status:check';
    protected $description = 'Check system health and store results';

    public function handle(): int
    {
        $checks = [
            'forum' => $this->checkForum(),
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'websocket' => $this->checkWebSocket(),
        ];

        $now = now();

        foreach ($checks as $service => $result) {
            // Skip auto-check if there's an active override for this service
            $override = StatusCheck::where('service', $service)
                ->where('is_override', true)
                ->latest('checked_at')
                ->first();

            if ($override) {
                continue;
            }

            StatusCheck::create([
                'service' => $service,
                'status' => $result['status'],
                'message' => $result['message'],
                'is_override' => false,
                'checked_at' => $now,
            ]);
        }

        // Prune records older than 24 hours (keep overrides)
        StatusCheck::where('is_override', false)
            ->where('checked_at', '<', now()->subHours(24))
            ->delete();

        $this->info('System status checks completed.');

        return self::SUCCESS;
    }

    protected function checkForum(): array
    {
        return [
            'status' => 'operational',
            'message' => null,
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');

            return [
                'status' => 'operational',
                'message' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'outage',
                'message' => 'Database connection failed.',
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $failedRecent = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subMinutes(15))
                ->count();

            if ($failedRecent > 10) {
                return [
                    'status' => 'degraded',
                    'message' => "High failure rate: {$failedRecent} failed jobs in last 15 minutes.",
                ];
            }

            return [
                'status' => 'operational',
                'message' => null,
            ];
        } catch (\Throwable) {
            // failed_jobs table may not exist
            return [
                'status' => 'operational',
                'message' => null,
            ];
        }
    }

    protected function checkWebSocket(): array
    {
        $host = config('broadcasting.connections.reverb.options.host', '127.0.0.1');
        $port = config('broadcasting.connections.reverb.options.port', 8080);
        $scheme = config('broadcasting.connections.reverb.options.scheme', 'http');

        if (! $host) {
            return [
                'status' => 'operational',
                'message' => 'Reverb not configured; skipped.',
            ];
        }

        try {
            $response = Http::timeout(5)->get("{$scheme}://{$host}:{$port}/");

            if ($response->successful() || $response->status() === 200) {
                return [
                    'status' => 'operational',
                    'message' => null,
                ];
            }

            return [
                'status' => 'degraded',
                'message' => 'Reverb returned HTTP ' . $response->status(),
            ];
        } catch (\Throwable) {
            return [
                'status' => 'outage',
                'message' => 'Reverb health endpoint unreachable.',
            ];
        }
    }
}
