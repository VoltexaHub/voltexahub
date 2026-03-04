<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Forum;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ChecksForumPermissions
{
    protected function getRole(Request $request): string
    {
        $user = $request->user();
        if (!$user) return 'guest';
        $role = $user->roles->first(fn ($r) => $r->name !== 'banned');
        return $role?->name ?? 'member';
    }

    protected function canView(Request $request, Forum $forum): bool
    {
        $perms = PermissionService::resolve($this->getRole($request), $forum->id);
        return $perms['can_view'];
    }

    protected function canPost(Request $request, Forum $forum): bool
    {
        $perms = PermissionService::resolve($this->getRole($request), $forum->id);
        return $perms['can_post'];
    }

    protected function canReply(Request $request, Forum $forum): bool
    {
        $perms = PermissionService::resolve($this->getRole($request), $forum->id);
        return $perms['can_reply'];
    }

    protected function denyView(): JsonResponse
    {
        return response()->json(['message' => 'You do not have permission to view this forum.'], 403);
    }

    protected function denyPost(): JsonResponse
    {
        return response()->json(['message' => 'You do not have permission to post in this forum.'], 403);
    }

    protected function denyReply(): JsonResponse
    {
        return response()->json(['message' => 'You do not have permission to reply in this forum.'], 403);
    }
}
