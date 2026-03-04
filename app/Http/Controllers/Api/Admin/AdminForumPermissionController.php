<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumPermission;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminForumPermissionController extends Controller
{
    private const ROLES = ['guest', 'member', 'vip', 'elite', 'moderator', 'admin'];

    public function index(Forum $forum): JsonResponse
    {
        $overrides = ForumPermission::where('forum_id', $forum->id)
            ->get()->keyBy('role_name');

        $groupDefaults = collect(PermissionService::allGroupDefaults())->keyBy('role_name');

        $perms = array_map(function ($role) use ($overrides, $groupDefaults) {
            $o = $overrides[$role] ?? null;
            $g = $groupDefaults[$role] ?? ['can_view' => true, 'can_post' => true, 'can_reply' => true];
            return [
                'role_name'         => $role,
                // null = inherit from group default
                'can_view'          => $o?->can_view,
                'can_post'          => $o?->can_post,
                'can_reply'         => $o?->can_reply,
                // resolved effective value
                'effective_view'    => $o?->can_view  ?? $g['can_view'],
                'effective_post'    => $o?->can_post   ?? $g['can_post'],
                'effective_reply'   => $o?->can_reply  ?? $g['can_reply'],
                // group defaults for display
                'group_view'        => $g['can_view'],
                'group_post'        => $g['can_post'],
                'group_reply'       => $g['can_reply'],
            ];
        }, self::ROLES);

        return response()->json([
            'data' => [
                'forum'       => ['id' => $forum->id, 'name' => $forum->name],
                'permissions' => $perms,
            ],
        ]);
    }

    public function update(Request $request, Forum $forum): JsonResponse
    {
        $validated = $request->validate([
            'permissions'               => 'required|array',
            'permissions.*.role_name'   => 'required|string',
            // nullable = inherit
            'permissions.*.can_view'    => 'nullable|boolean',
            'permissions.*.can_post'    => 'nullable|boolean',
            'permissions.*.can_reply'   => 'nullable|boolean',
        ]);

        foreach ($validated['permissions'] as $p) {
            // If all are null (inherit), delete the override row entirely
            if (is_null($p['can_view'] ?? null) && is_null($p['can_post'] ?? null) && is_null($p['can_reply'] ?? null)) {
                ForumPermission::where('forum_id', $forum->id)
                    ->where('role_name', $p['role_name'])
                    ->delete();
                continue;
            }

            ForumPermission::updateOrCreate(
                ['forum_id' => $forum->id, 'role_name' => $p['role_name']],
                [
                    'can_view'  => $p['can_view']  ?? null,
                    'can_post'  => $p['can_post']   ?? null,
                    'can_reply' => $p['can_reply']  ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Permissions updated.']);
    }
}
