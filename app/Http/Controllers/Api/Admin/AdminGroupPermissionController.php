<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupPermission;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGroupPermissionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => PermissionService::allGroupDefaults(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permissions'               => 'required|array',
            'permissions.*.role_name'   => 'required|string',
            'permissions.*.can_view'    => 'boolean',
            'permissions.*.can_post'    => 'boolean',
            'permissions.*.can_reply'   => 'boolean',
        ]);

        foreach ($validated['permissions'] as $p) {
            GroupPermission::updateOrCreate(
                ['role_name' => $p['role_name']],
                [
                    'can_view'  => $p['can_view']  ?? true,
                    'can_post'  => $p['can_post']   ?? true,
                    'can_reply' => $p['can_reply']  ?? true,
                ]
            );
        }

        return response()->json(['message' => 'Group permissions updated.']);
    }
}
