<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AdminReAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Admin-Reauth');

        if (!$token) {
            return response()->json([
                'message' => 'Re-authentication required.',
            ], 423);
        }

        $user = $request->user();

        if (!$user || !Cache::has("reauth:{$user->id}:{$token}")) {
            return response()->json([
                'message' => 'Re-authentication required.',
            ], 423);
        }

        return $next($request);
    }
}
