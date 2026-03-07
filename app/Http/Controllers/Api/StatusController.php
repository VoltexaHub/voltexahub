<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StatusCheck;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $services = StatusCheck::latestPerService()
                ->orderBy('service')
                ->get();

            $statuses = $services->pluck('status')->toArray();

            if (in_array('outage', $statuses)) {
                $overall = 'outage';
            } elseif (in_array('degraded', $statuses)) {
                $overall = 'degraded';
            } else {
                $overall = 'operational';
            }

            return response()->json([
                'overall' => $overall,
                'services' => $services->map(fn ($s) => [
                    'service' => $s->service,
                    'status' => $s->status,
                    'message' => $s->message,
                    'is_override' => $s->is_override,
                    'checked_at' => $s->checked_at->toIso8601String(),
                ]),
            ]);
        } catch (\Illuminate\Database\QueryException) {
            return response()->json([
                'overall' => 'operational',
                'services' => [],
            ]);
        }
    }
}
