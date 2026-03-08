<?php

namespace App\Http\Controllers\Api\Staff;

use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Post;
use App\Models\Report;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserAward;
use App\Notifications\AwardReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffModerationController extends Controller
{
    protected function hasStaffPermission(string $key): bool
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) return true;
        foreach ($user->roles as $role) {
            $perms = $role->staff_permissions ?? [];
            if (in_array($key, $perms)) return true;
        }
        return false;
    }

    protected function denyPermission(string $key): JsonResponse
    {
        return response()->json([
            'message' => "Your role does not have the {$key} permission.",
        ], 403);
    }

    // --- Reports ---

    public function reports(Request $request): JsonResponse
    {
        if (!$this->hasStaffPermission('view_reports')) {
            return $this->denyPermission('view_reports');
        }

        $status = $request->input('status', 'pending');

        $reports = Report::where('status', $status)
            ->with([
                'reporter:id,username',
                'post:id,thread_id,content',
                'post.thread:id,title,slug',
                'thread:id,title,slug',
            ])
            ->latest()
            ->take(50)
            ->get();

        return response()->json(['data' => $reports]);
    }

    public function updateReport(int $id, Request $request): JsonResponse
    {
        if (!$this->hasStaffPermission('view_reports')) {
            return $this->denyPermission('view_reports');
        }

        $report = Report::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,resolved,dismissed'],
        ]);

        $report->update($validated);

        return response()->json([
            'data' => $report->fresh(),
            'message' => 'Report updated.',
        ]);
    }

    // --- Threads ---

    public function threads(Request $request): JsonResponse
    {
        if (!$this->hasStaffPermission('manage_threads')) {
            return $this->denyPermission('manage_threads');
        }

        $query = Thread::with(['user:id,username', 'forum:id,name,slug']);

        if ($request->has('is_pinned')) {
            $query->where('is_pinned', $request->boolean('is_pinned'));
        }
        if ($request->has('is_locked')) {
            $query->where('is_locked', $request->boolean('is_locked'));
        }
        if ($request->has('is_solved')) {
            $query->where('is_solved', $request->boolean('is_solved'));
        }
        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $threads = $query->latest()->paginate(20);

        return response()->json([
            'data' => $threads->items(),
            'meta' => [
                'current_page' => $threads->currentPage(),
                'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(),
                'total' => $threads->total(),
            ],
        ]);
    }

    public function pinThread(int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('manage_threads')) {
            return $this->denyPermission('manage_threads');
        }

        $thread = Thread::findOrFail($id);
        $thread->update(['is_pinned' => !$thread->is_pinned]);

        return response()->json([
            'data' => $thread->fresh(),
            'message' => $thread->is_pinned ? 'Thread pinned.' : 'Thread unpinned.',
        ]);
    }

    public function lockThread(int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('manage_threads')) {
            return $this->denyPermission('manage_threads');
        }

        $thread = Thread::findOrFail($id);
        $thread->update(['is_locked' => !$thread->is_locked]);

        return response()->json([
            'data' => $thread->fresh(),
            'message' => $thread->is_locked ? 'Thread locked.' : 'Thread unlocked.',
        ]);
    }

    public function solveThread(int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('manage_threads')) {
            return $this->denyPermission('manage_threads');
        }

        $thread = Thread::findOrFail($id);
        $thread->update(['is_solved' => !$thread->is_solved]);

        return response()->json([
            'data' => $thread->fresh(),
            'message' => $thread->is_solved ? 'Thread marked as solved.' : 'Thread marked as unsolved.',
        ]);
    }

    public function deleteThread(int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('manage_threads')) {
            return $this->denyPermission('manage_threads');
        }

        $thread = Thread::findOrFail($id);
        $forum = $thread->forum;

        $postCount = $thread->posts()->count();
        $thread->posts()->forceDelete();
        $thread->delete();

        $forum->decrement('thread_count');
        $forum->decrement('post_count', max(0, $postCount));

        $latestPost = $forum->threads()
            ->with(['posts' => fn($q) => $q->orderByDesc('created_at')->limit(1), 'posts.user:id,username,avatar_color'])
            ->orderByDesc('last_reply_at')
            ->first()
            ?->posts
            ?->first();

        $forum->update([
            'last_post_at'      => $latestPost?->created_at,
            'last_post_user_id' => $latestPost?->user_id,
        ]);

        return response()->json(['message' => 'Thread deleted.']);
    }

    // --- Posts ---

    public function deletePost(int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('manage_posts')) {
            return $this->denyPermission('manage_posts');
        }

        $post = Post::findOrFail($id);

        $thread = $post->thread;
        $forum = $thread->forum;

        $post->forceDelete();

        $thread->decrement('reply_count');
        $forum->decrement('post_count');

        return response()->json(['message' => 'Post permanently deleted.']);
    }

    // --- Users ---

    public function users(Request $request): JsonResponse
    {
        if (!$this->hasStaffPermission('ban_users')) {
            return $this->denyPermission('ban_users');
        }

        $query = User::with('roles');

        if ($search = $request->input('search')) {
            $query->where('username', 'like', "%{$search}%");
        }

        $users = $query->latest()->paginate(20);

        return response()->json([
            'data' => collect($users->items())->map(fn ($u) => [
                'id' => $u->id,
                'username' => $u->username,
                'avatar_url' => $u->avatar_url,
                'avatar_color' => $u->avatar_color,
                'roles' => $u->roles->map(fn ($r) => [
                    'name' => $r->name,
                    'color' => $r->color ?? '#6b7280',
                    'label' => $r->label ?? ucfirst($r->name),
                ]),
                'created_at' => $u->created_at,
                'is_banned' => $u->roles->contains('name', 'banned'),
            ])->all(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function banUser(Request $request, int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('ban_users')) {
            return $this->denyPermission('ban_users');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->assignRole('banned');
        $user->update([
            'user_title' => 'Banned' . ($validated['reason'] ? ': ' . $validated['reason'] : ''),
        ]);

        return response()->json([
            'data' => $user->fresh()->load('roles'),
            'message' => 'User banned successfully.',
        ]);
    }

    public function unbanUser(int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('ban_users')) {
            return $this->denyPermission('ban_users');
        }

        $user = User::findOrFail($id);
        $user->removeRole('banned');

        return response()->json([
            'data' => $user->fresh()->load('roles'),
            'message' => 'User unbanned successfully.',
        ]);
    }

    // --- Awards ---

    public function awards(): JsonResponse
    {
        if (!$this->hasStaffPermission('grant_awards')) {
            return $this->denyPermission('grant_awards');
        }

        $awards = Award::all();

        return response()->json(['data' => $awards]);
    }

    public function grantAward(Request $request, int $id): JsonResponse
    {
        if (!$this->hasStaffPermission('grant_awards')) {
            return $this->denyPermission('grant_awards');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'award_id' => ['required', 'exists:awards,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $userAward = UserAward::create([
            'user_id' => $user->id,
            'award_id' => $validated['award_id'],
            'granted_by' => auth()->id(),
            'reason' => $validated['reason'] ?? null,
        ]);

        $award = Award::find($validated['award_id']);
        $user->notify(new AwardReceivedNotification($award));
        broadcast(new NewNotification($user->id, [
            'type' => 'award_received',
            'title' => 'Award received!',
            'body' => 'You received the "' . $award->name . '" award',
            'url' => '/profile',
        ]));

        return response()->json([
            'data' => $userAward->load('award'),
            'message' => 'Award granted successfully.',
        ], 201);
    }

    public function revokeAward(int $id, int $awardId): JsonResponse
    {
        if (!$this->hasStaffPermission('grant_awards')) {
            return $this->denyPermission('grant_awards');
        }

        $userAward = UserAward::where('user_id', $id)
            ->where('id', $awardId)
            ->firstOrFail();

        $userAward->delete();

        return response()->json(['message' => 'Award revoked successfully.']);
    }
}
