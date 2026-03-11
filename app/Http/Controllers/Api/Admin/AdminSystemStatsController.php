<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminSystemStatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'disk'     => $this->getDisk(),
                'memory'   => $this->getMemory(),
                'cpu'      => $this->getCpu(),
                'uptime'   => $this->getUptime(),
                'database' => $this->getDatabase(),
                'versions' => $this->getVersions(),
            ],
        ]);
    }

    private function getDisk(): array
    {
        $total = disk_total_space('/');
        $free  = disk_free_space('/');
        $used  = $total - $free;
        return [
            'total'   => $total,
            'used'    => $used,
            'free'    => $free,
            'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
        ];
    }

    private function getMemory(): array
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return ['total' => 0, 'used' => 0, 'free' => 0, 'percent' => 0];
        }
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
        $totalKb     = (int) ($total[1] ?? 0);
        $availableKb = (int) ($available[1] ?? 0);
        $usedKb      = $totalKb - $availableKb;
        return [
            'total'   => $totalKb * 1024,
            'used'    => $usedKb * 1024,
            'free'    => $availableKb * 1024,
            'percent' => $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0,
        ];
    }

    private function getCpu(): array
    {
        $load = sys_getloadavg();
        return [
            'load_1'  => round($load[0] ?? 0, 2),
            'load_5'  => round($load[1] ?? 0, 2),
            'load_15' => round($load[2] ?? 0, 2),
        ];
    }

    private function getUptime(): array
    {
        $contents = @file_get_contents('/proc/uptime');
        if (!$contents) return ['seconds' => 0, 'human' => 'Unknown'];
        $seconds = (int) explode(' ', $contents)[0];
        $days    = floor($seconds / 86400);
        $hours   = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $parts   = [];
        if ($days)    $parts[] = "{$days}d";
        if ($hours)   $parts[] = "{$hours}h";
        if ($minutes) $parts[] = "{$minutes}m";
        return [
            'seconds' => $seconds,
            'human'   => $parts ? implode(' ', $parts) : 'Just started',
        ];
    }

    private function getDatabase(): array
    {
        try {
            $dbName = config('database.connections.mysql.database');
            $row = DB::select("
                SELECT ROUND(SUM(data_length + index_length), 0) AS size
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName]);
            $size = (int) ($row[0]->size ?? 0);
            return ['size' => $size, 'name' => $dbName];
        } catch (\Throwable) {
            return ['size' => 0, 'name' => ''];
        }
    }

    private function getVersions(): array
    {
        return [
            'php'     => PHP_VERSION,
            'laravel' => app()->version(),
        ];
    }
}
