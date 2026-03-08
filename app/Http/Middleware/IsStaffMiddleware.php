<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsStaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        foreach ($user->roles as $role) {
            if ($role->is_staff) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'You do not have staff access.'], 403);
    }
}
