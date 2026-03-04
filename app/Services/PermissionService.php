<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\GroupPermission;
use App\Models\ForumPermission;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    private const ROLES = ['guest', 'member', 'vip', 'elite', 'moderator', 'admin'];

    /**
     * Get resolved permissions for a role in a forum.
     * Forum override takes precedence; null = fall back to group default.
     */
    public static function resolve(string $role, int $forumId): array
    {
        $group = static::groupDefaults($role);
        $override = ForumPermission::where('forum_id', $forumId)
            ->where('role_name', $role)
            ->first();

        return [
            'can_view'  => $override?->can_view  ?? $group['can_view'],
            'can_post'  => $override?->can_post   ?? $group['can_post'],
            'can_reply' => $override?->can_reply  ?? $group['can_reply'],
        ];
    }

    /**
     * Get group-level defaults for a role.
     */
    public static function groupDefaults(string $role): array
    {
        $gp = GroupPermission::where('role_name', $role)->first();
        if ($gp) {
            return [
                'can_view'  => $gp->can_view,
                'can_post'  => $gp->can_post,
                'can_reply' => $gp->can_reply,
            ];
        }

        // Hardcoded sensible defaults if not seeded
        return match ($role) {
            'guest'     => ['can_view' => true,  'can_post' => false, 'can_reply' => false],
            'banned'    => ['can_view' => false, 'can_post' => false, 'can_reply' => false],
            default     => ['can_view' => true,  'can_post' => true,  'can_reply' => true],
        };
    }

    /**
     * All roles with their group defaults.
     */
    public static function allGroupDefaults(): array
    {
        $rows = GroupPermission::all()->keyBy('role_name');
        return array_map(fn($role) => [
            'role_name' => $role,
            'can_view'  => $rows[$role]?->can_view  ?? ($role === 'banned' ? false : true),
            'can_post'  => $rows[$role]?->can_post   ?? ($role === 'guest' || $role === 'banned' ? false : true),
            'can_reply' => $rows[$role]?->can_reply  ?? ($role === 'guest' || $role === 'banned' ? false : true),
        ], self::ROLES);
    }
}
