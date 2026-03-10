<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PendingEmailChange;
use App\Models\User;
use App\Services\PerkService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['activeCosmetic.storeItem']);
        $user->load('roles'); // fresh load to bypass Spatie role cache

        $user->unread_notifications_count = $user->unreadNotifications()->count();
        $user->active_cosmetics = $user->cosmetics()
            ->where('is_active', true)
            ->with('storeItem')
            ->get();

        $boost = \App\Models\UserXpBoost::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();
        $user->xp_boost_active = $boost ? true : false;
        $user->xp_boost_multiplier = $boost?->multiplier;
        $user->xp_boost_expires_at = $boost?->expires_at?->toIso8601String();

        $perkService = app(PerkService::class);
        $perkTypes = [
            PerkService::NO_ADS, PerkService::BYPASS_UNLOCK, PerkService::PROFILE_COVER,
            PerkService::CUSTOM_CSS, PerkService::LOCKED_BYPASS, PerkService::CHANGE_USERNAME,
            PerkService::USERBAR_HUE, PerkService::USERNAME_COLOR, PerkService::AWARDS_REORDER,
            PerkService::PRE_ACCESS,
        ];
        $user->perks = array_values(array_filter($perkTypes, fn ($t) => $perkService->userHasPerk($user, $t)));

        // Staff data
        $isAdmin = $user->hasRole('admin');
        $isStaff = $isAdmin || $user->roles->contains(fn ($r) => $r->is_staff);

        $allStaffPerms = ['view_reports', 'manage_threads', 'manage_posts', 'ban_users', 'grant_awards'];

        if ($isAdmin) {
            $staffPermissions = $allStaffPerms;
        } else {
            $staffPermissions = [];
            foreach ($user->roles as $role) {
                foreach ($role->staff_permissions ?? [] as $perm) {
                    if (!in_array($perm, $staffPermissions)) {
                        $staffPermissions[] = $perm;
                    }
                }
            }
        }

        $user->is_staff = $isStaff;
        $user->staff_permissions = $staffPermissions;

        return response()->json([
            'data' => $user,
        ]);
    }

    public function profile(string $username): JsonResponse
    {
        $user = User::where('username', $username)
            ->firstOrFail();

        $user->load([
            'roles',
            'userAwards.award',
            'userAchievements' => fn($q) => $q->whereNotNull('unlocked_at')->with('achievement'),
            'activeCosmetic.storeItem',
        ]);

        $recentPosts = $user->posts()
            ->with('thread:id,title,slug')
            ->latest()
            ->take(5)
            ->get();

        // Recent activity (last 8 posts with thread/forum info)
        $recentActivity = $user->posts()
            ->with(['thread:id,title,slug,forum_id', 'thread.forum:id,name,slug'])
            ->latest()
            ->take(8)
            ->get(['id', 'thread_id', 'created_at', 'body'])
            ->map(fn($p) => [
                'id' => $p->id,
                'thread_title' => $p->thread?->title,
                'thread_slug' => $p->thread?->slug,
                'forum_name' => $p->thread?->forum?->name,
                'forum_slug' => $p->thread?->forum?->slug,
                'created_at' => $p->created_at?->toISOString(),
                'excerpt' => mb_strimwidth(strip_tags($p->body ?? ''), 0, 120, '...'),
            ]);

        $isOnline = $user->last_seen && $user->last_seen->gte(now()->subMinutes(15));
        $threadCount = $user->threads()->count();

        // Reputation (total likes received on all posts)
        $reputation = DB::table('post_likes')
            ->join('posts', 'post_likes.post_id', '=', 'posts.id')
            ->where('posts.user_id', $user->id)
            ->count();

        // Likes given
        $likesGiven = DB::table('post_likes')->where('user_id', $user->id)->count();

        // Replies made (posts minus threads started)
        $repliesMade = max(0, ($user->post_count ?? 0) - $threadCount);

        // Pinned thread
        $pinnedThread = null;
        if ($user->pinned_thread_id) {
            $pt = $user->pinnedThread()->with('forum:id,name,slug')->first();
            if ($pt) {
                $pinnedThread = [
                    'id' => $pt->id,
                    'title' => $pt->title,
                    'slug' => $pt->slug,
                    'reply_count' => $pt->reply_count ?? 0,
                    'created_at' => $pt->created_at?->toISOString(),
                    'forum' => $pt->forum ? [
                        'id' => $pt->forum->id,
                        'name' => $pt->forum->name,
                        'slug' => $pt->forum->slug,
                    ] : null,
                ];
            }
        }

        $xp = $user->xp ?? 0;
        $currentLevel = XpService::levelFor($xp);
        $nextLevel = XpService::nextLevel($xp);
        $levelProgress = XpService::progressPercent($xp);

        // Years of service
        $yearsOfService = null;
        if ($user->created_at) {
            $days = (int) $user->created_at->diffInDays(now());
            if ($days >= 1825) $yearsOfService = '5+ years';
            elseif ($days >= 1460) $yearsOfService = '4 years';
            elseif ($days >= 1095) $yearsOfService = '3 years';
            elseif ($days >= 730) $yearsOfService = '2 years';
            elseif ($days >= 365) $yearsOfService = '1 year';
            elseif ($days >= 180) $yearsOfService = '6 months';
            elseif ($days >= 90) $yearsOfService = '3 months';
            elseif ($days >= 30) $yearsOfService = '1 month';
            elseif ($days >= 7) $yearsOfService = '1 week';
        }

        $boost = \App\Models\UserXpBoost::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();

        return response()->json([
            "data" => [
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "avatar_url" => $user->avatar_url,
                "avatar_color" => $user->avatar_color,
                "user_title" => $user->user_title,
                "bio" => $user->bio,
                "status" => $user->status,
                "signature" => $user->signature,
                "post_count" => $user->post_count,
                "thread_count" => $threadCount,
                "credits" => $user->credits,
                "xp" => $xp,
                "level" => $currentLevel?->level,
                "level_label" => $currentLevel?->label,
                "xp_next_level" => $nextLevel?->xp_required,
                "level_progress" => $levelProgress,
                "years_of_service" => $yearsOfService,
                "join_date" => $user->created_at?->toISOString(),
                "last_seen" => $user->last_seen?->toISOString(),
                "is_online" => $isOnline,
                "roles" => $user->roles->map(fn($r) => [
                    "name" => $r->name,
                    "color" => $r->color ?? "#6b7280",
                    "label" => $r->label ?? ucfirst($r->name),
                ]),
                "primary_role" => ($pr = $user->roles->first(fn($r) => $r->name !== 'banned') ?? $user->roles->first()) ? [
                    "name" => $pr->name,
                    "color" => $pr->color ?? "#6b7280",
                    "label" => $pr->label ?? ucfirst($pr->name),
                ] : null,
                "awards" => $user->userAwards->map(fn($ua) => [
                    "id" => $ua->id,
                    "name" => $ua->award->name ?? "",
                    "description" => $ua->award->description ?? "",
                    "icon_url" => $ua->award->icon_url ?? null,
                ]),
                "achievements" => $user->userAchievements->map(fn($ua) => [
                    "id" => $ua->id,
                    "name" => $ua->achievement->name ?? "",
                    "description" => $ua->achievement->description ?? "",
                    "icon" => $ua->achievement->icon ?? "fa-solid fa-star",
                    "unlocked" => true,
                    "unlocked_at" => $ua->unlocked_at?->toISOString(),
                ]),
                "recent_posts" => $recentPosts->map(fn($p) => [
                    "id" => $p->id,
                    "thread_id" => $p->thread_id,
                    "thread_title" => $p->thread?->title,
                    "excerpt" => \Illuminate\Support\Str::limit(strip_tags($p->body), 120),
                    "created_at" => $p->created_at?->toISOString(),
                ]),
                "discord_username" => $user->discord_username ?? null,
                "twitter_handle" => $user->twitter_handle ?? null,
                "website_url" => $user->website_url ?? null,
                "minecraft_ign" => $user->minecraft_ign ?? null,
                "minecraft_username" => $user->minecraft_username ?? null,
                "minecraft_uuid" => $user->minecraft_uuid ?? null,
                "minecraft_verified" => (bool) $user->minecraft_verified,
                "github_username" => $user->github_username ?? null,
                "is_sponsor" => (bool) $user->is_sponsor,
                "sponsor_since" => $user->sponsor_since?->toISOString(),
                "sponsor_tier" => $user->sponsor_tier,
                "cover_url" => $user->cover_url,
                "cover_overlay_opacity" => $user->cover_overlay_opacity ?? 20,
                "custom_css" => $user->custom_css,
                "username_color" => $user->username_color,
                "userbar_hue" => $user->userbar_hue,
                "xp_boost_active" => $boost ? true : false,
                "xp_boost_multiplier" => $boost?->multiplier,
                "xp_boost_expires_at" => $boost?->expires_at?->toIso8601String(),
                "reputation" => $reputation,
                "likes_given" => $likesGiven,
                "replies_made" => $repliesMade,
                "pinned_thread" => $pinnedThread,
                "recent_activity" => $recentActivity,
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'user_title' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'max:100'],
            'signature' => ['nullable', 'string', 'max:500'],
            'avatar_color' => ['nullable', 'string', 'max:7'],
            'discord_username' => ['nullable', 'string', 'max:100'],
            'twitter_handle' => ['nullable', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'minecraft_ign' => ['nullable', 'string', 'max:50'],
            'rust_steam_id' => ['nullable', 'string', 'max:50'],
            'github_username' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($validated);

        // Auto-check sponsor status when github_username changes
        if (array_key_exists('github_username', $validated)) {
            $ghUser = $validated['github_username'];
            if ($ghUser) {
                $sponsor = \App\Models\GithubSponsor::where('github_login', $ghUser)
                    ->where('active', true)
                    ->first();
                if ($sponsor) {
                    $user->update([
                        'is_sponsor' => true,
                        'sponsor_since' => $sponsor->sponsored_at ?? now(),
                        'sponsor_tier' => $sponsor->tier,
                    ]);
                } else {
                    $user->update([
                        'is_sponsor' => false,
                        'sponsor_since' => null,
                        'sponsor_tier' => null,
                    ]);
                }
            } else {
                $user->update([
                    'is_sponsor' => false,
                    'sponsor_since' => null,
                    'sponsor_tier' => null,
                ]);
            }
        }

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function updateAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required_with:new_password,email', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Current password is required for email or password changes
        if (isset($validated['email']) || !empty($validated['new_password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                ], 422);
            }
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            // Save to pending_email and send verification to OLD address
            $user->pending_email = $validated['email'];
            $user->save();

            $signedUrl = URL::temporarySignedRoute(
                'confirm-email-change',
                now()->addHours(24),
                ['user' => $user->id]
            );

            Mail::to($user->email)->send(new PendingEmailChange($user, $signedUrl));

            return response()->json([
                'data' => $user->fresh(),
                'message' => 'A confirmation link has been sent to your current email address.',
            ]);
        }

        if (!empty($validated['new_password'])) {
            $user->password = $validated['new_password'];
        }

        $user->save();

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'Account updated successfully.',
        ]);
    }

    public function credits(Request $request): JsonResponse
    {
        $user = $request->user();

        $log = $user->creditsLog()
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'data' => [
                'balance' => $user->credits,
                'log' => $log->items(),
            ],
            'meta' => [
                'current_page' => $log->currentPage(),
                'last_page' => $log->lastPage(),
                'per_page' => $log->perPage(),
                'total' => $log->total(),
            ],
        ]);
    }

    public function achievements(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->checkAchievements();

        $achievements = $user->userAchievements()
            ->whereNotNull('unlocked_at')
            ->with('achievement')
            ->get();

        return response()->json([
            'data' => $achievements,
        ]);
    }

    public function awards(Request $request): JsonResponse
    {
        $awards = $request->user()->userAwards()
            ->with(['award', 'grantedByUser'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $awards,
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function cosmetics(Request $request): JsonResponse
    {
        $cosmetics = $request->user()->cosmetics()
            ->with('storeItem')
            ->get();

        return response()->json([
            'data' => $cosmetics,
        ]);
    }

    public function toggleCosmetic(Request $request, int $id): JsonResponse
    {
        $cosmetic = $request->user()->cosmetics()->findOrFail($id);
        $cosmetic->update(['is_active' => ! $cosmetic->is_active]);

        return response()->json([
            'data' => $cosmetic->fresh(),
            'message' => 'Cosmetic toggled.',
        ]);
    }

    public function updateNotificationSettings(Request $request): JsonResponse
    {
        // Placeholder for notification preferences - can be extended
        return response()->json([
            'message' => 'Notification settings updated.',
        ]);
    }

    public function updatePrivacySettings(Request $request): JsonResponse
    {
        // Placeholder for privacy preferences - can be extended
        return response()->json([
            'message' => 'Privacy settings updated.',
        ]);
    }

    public function updatePinnedThread(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'thread_id' => ['nullable', 'integer'],
        ]);

        if ($validated['thread_id']) {
            $thread = $user->threads()->where('id', $validated['thread_id'])->firstOrFail();
            $user->update(['pinned_thread_id' => $thread->id]);
        } else {
            $user->update(['pinned_thread_id' => null]);
        }

        return response()->json([
            'message' => $validated['thread_id'] ? 'Thread pinned to profile.' : 'Thread unpinned from profile.',
        ]);
    }

    public function online(): JsonResponse
    {
        $users = User::where('last_seen', '>=', now()->subMinutes(5))
            ->select('id', 'username', 'avatar_path')
            ->orderByDesc('last_seen')
            ->get();

        return response()->json([
            'data' => $users,
            'count' => $users->count(),
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $sessions = $request->user()->sessions()
            ->latest('last_active_at')
            ->get();

        return response()->json([
            'data' => $sessions,
        ]);
    }

    public function destroySession(Request $request, int $id): JsonResponse
    {
        $session = $request->user()->sessions()->findOrFail($id);
        $session->delete();

        return response()->json([
            'message' => 'Session terminated.',
        ]);
    }

    public function members(\Illuminate\Http\Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 24), 48);
        $sort = $request->get('sort', 'joined'); // joined|posts|credits|username
        $search = $request->get('q', '');

        $letter = $request->get('letter', '');

        $query = User::with('roles')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'banned'))
            ->when($search, fn ($q) => $q->where('username', 'like', "%{$search}%"))
            ->when($letter && $letter !== '#', fn ($q) => $q->where('username', 'like', "{$letter}%"))
            ->when($letter === '#', fn ($q) => $q->whereRaw('username NOT REGEXP \'^[a-zA-Z]\''));

        $query->orderBy(match ($sort) {
            'posts'    => 'post_count',
            'credits'  => 'credits',
            'username' => 'username',
            default    => 'created_at',
        }, $sort === 'username' ? 'asc' : 'desc');

        $members = $query->paginate($perPage);

        return response()->json([
            'data' => $members->through(fn ($u) => [
                'id'           => $u->id,
                'username'     => $u->username,
                'avatar_url'   => $u->avatar_url,
                'avatar_color' => $u->avatar_color,
                'post_count'   => $u->post_count ?? 0,
                'credits'      => $u->credits ?? 0,
                'joined'       => $u->created_at,
                'is_online'    => $u->last_seen && $u->last_seen->gte(now()->subMinutes(15)),
                'primary_role' => ($pr = $u->roles->first(fn ($r) => $r->name !== 'banned') ?? $u->roles->first()) ? [
                    'name'  => $pr->name,
                    'label' => $pr->label ?? ucfirst($pr->name),
                    'color' => $pr->color ?? '#6b7280',
                ] : null,
                'custom_title' => $u->custom_title ?? null,
            ]),
            'meta' => [
                'total'        => $members->total(),
                'current_page' => $members->currentPage(),
                'last_page'    => $members->lastPage(),
            ],
        ]);
    }

    public function staff(): JsonResponse
    {
        // Use is_staff roles ordered by priority (highest first)
        $staffRoles = \App\Models\Role::where('is_staff', true)
            ->orderByDesc('priority')
            ->get();

        $staffUserIds = \DB::table('model_has_roles')
            ->whereIn('role_id', $staffRoles->pluck('id'))
            ->pluck('model_id');

        $staffUsers = User::with('roles')
            ->whereIn('id', $staffUserIds)
            ->get();

        $grouped = $staffRoles->mapWithKeys(function ($role) use ($staffUsers) {
            $members = $staffUsers->filter(
                fn ($u) => $u->roles->contains('id', $role->id)
            )->values();

            return [(string) $role->id => [
                'role' => [
                    'id'    => $role->id,
                    'name'  => $role->name,
                    'label' => $role->label ?? ucfirst($role->name),
                    'color' => $role->color ?? '#6b7280',
                ],
                'members' => $members->map(fn ($u) => [
                    'id'           => $u->id,
                    'username'     => $u->username,
                    'avatar_url'   => $u->avatar_url,
                    'avatar_color' => $u->avatar_color,
                    'post_count'   => $u->post_count ?? 0,
                    'joined'       => $u->created_at,
                    'is_online'    => $u->last_seen && $u->last_seen->gte(now()->subMinutes(15)),
                ]),
            ]];
        });

        return response()->json(['data' => $grouped]);
    }
}
