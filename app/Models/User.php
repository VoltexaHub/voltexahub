<?php

namespace App\Models;

use App\Models\ForumConfig;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Events\NewNotification;
use App\Notifications\AchievementUnlockedNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'user_title',
        'bio',
        'signature',
        'avatar_color',
        'avatar_path',
        'postbit_bg',
        'credits',
        'xp',
        'post_count',
        'is_online',
        'last_active_at',
        'last_seen',
        'discord_username',
        'twitter_handle',
        'website_url',
        'minecraft_ign',
        'minecraft_username',
        'minecraft_uuid',
        'minecraft_verified',
        'minecraft_verified_at',
        'rust_steam_id',
        'rust_verified',
        'github_username',
        'is_sponsor',
        'sponsor_since',
        'sponsor_tier',
        'cover_photo_path',
        'cover_overlay_opacity',
        'custom_css',
        'username_color',
        'userbar_hue',
        'username_changed_at',
        'awards_sort_order',
        'status',
        'pinned_thread_id',
        'known_ips',
        'pending_email',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $appends = ['avatar_url', 'group_color', 'group_label', 'cover_url', 'email_verified'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'last_active_at' => 'datetime',
            'last_seen' => 'datetime',
            'minecraft_verified' => 'boolean',
            'minecraft_verified_at' => 'datetime',
            'rust_verified' => 'boolean',
            'credits' => 'integer',
            'xp' => 'integer',
            'post_count' => 'integer',
            'username_changed_at' => 'datetime',
            'awards_sort_order' => 'array',
            'is_sponsor' => 'boolean',
            'sponsor_since' => 'datetime',
            'known_ips' => 'array',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path
            ? '/storage/' . $this->avatar_path
            : rtrim(env('FRONTEND_URL', env('APP_URL', 'https://community.voltexahub.com')), '/') . '/default-avatar.png'
        );
    }

    protected function emailVerified(): Attribute
    {
        return Attribute::get(fn () => !is_null($this->email_verified_at));
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_photo_path
            ? Storage::disk('public')->url($this->cover_photo_path)
            : null
        );
    }

    protected function groupColor(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->relationLoaded('roles')) return '#6b7280';
            $role = $this->roles->first(fn ($r) => $r->name !== 'banned') ?? $this->roles->first();
            return $role?->color ?? '#6b7280';
        });
    }

    protected function groupLabel(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->relationLoaded('roles')) return null;
            $role = $this->roles->first(fn ($r) => $r->name !== 'banned') ?? $this->roles->first();
            return $role?->label ?? ($role ? ucfirst($role->name) : null);
        });
    }

    public function sendPasswordResetNotification($token): void
    {
        $frontendUrl = config('app.frontend_url', 'https://community.voltexahub.com');
        $url = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($this->getEmailForPasswordReset());

        ResetPassword::createUrlUsing(fn () => $url);

        $this->notify(new ResetPassword($token));
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function userAwards(): HasMany
    {
        return $this->hasMany(UserAward::class);
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function creditsLog(): HasMany
    {
        return $this->hasMany(CreditsLog::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(StorePurchase::class);
    }

    public function cosmetics(): HasMany
    {
        return $this->hasMany(UserCosmetic::class);
    }

    public function activeBoost(): HasOne
    {
        return $this->hasOne(UserXpBoost::class)
            ->where('expires_at', '>', now())
            ->orderByDesc('expires_at')
            ->limit(1);
    }

    public function activeCosmetic(): HasOne
    {
        return $this->hasOne(UserCosmetic::class)->where('is_active', true);
    }

    public function pinnedThread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'pinned_thread_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function addCredits(int $amount, string $reason, ?string $referenceType = null, ?int $referenceId = null): void
    {
        // Apply role-based credit multiplier
        $multipliers = json_decode(ForumConfig::get('role_credit_multipliers', '{}'), true) ?: [];
        $highestMultiplier = 1.0;
        foreach ($this->roles as $role) {
            if (isset($multipliers[$role->name]) && (float) $multipliers[$role->name] > $highestMultiplier) {
                $highestMultiplier = (float) $multipliers[$role->name];
            }
        }
        $amount = (int) round($amount * $highestMultiplier);

        $this->increment('credits', $amount);

        CreditsLog::create([
            'user_id' => $this->id,
            'amount' => $amount,
            'balance_after' => $this->fresh()->credits,
            'reason' => $reason,
            'type' => 'earn',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function spendCredits(int $amount, string $reason, ?string $referenceType = null, ?int $referenceId = null): bool
    {
        if ($this->credits < $amount) {
            return false;
        }

        $this->decrement('credits', $amount);

        CreditsLog::create([
            'user_id' => $this->id,
            'amount' => -$amount,
            'balance_after' => $this->fresh()->credits,
            'reason' => $reason,
            'type' => 'spend',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        return true;
    }

    public function checkAchievements(): void
    {
        $achievements = Achievement::all();

        foreach ($achievements as $achievement) {
            $userAchievement = UserAchievement::firstOrCreate(
                ['user_id' => $this->id, 'achievement_id' => $achievement->id],
                ['progress' => 0]
            );

            if ($userAchievement->unlocked_at) {
                continue;
            }

            $progress = match ($achievement->trigger_key) {
                'post_count' => $this->post_count,
                'thread_count' => $this->threads()->count(),
                'reactions_received' => $this->posts()->sum('reaction_count'),
                'account_age_days' => $this->created_at ? (int) $this->created_at->diffInDays(now()) : 0,
                'purchases' => $this->purchases()->where('status', 'completed')->count(),
                'credits_spent' => abs($this->creditsLog()->where('type', 'spend')->sum('amount')),
                'solutions' => $this->posts()
                    ->whereHas('thread', fn ($q) => $q->where('is_solved', true))
                    ->where('is_first_post', false)
                    ->count(),
                default => 0,
            };

            $userAchievement->progress = (int) ($progress ?? 0);

            if ($progress >= $achievement->trigger_value) {
                $userAchievement->unlocked_at = now();
                if ($achievement->credits_reward > 0) {
                    $this->addCredits($achievement->credits_reward, "Achievement: {$achievement->name}");
                }
                $this->notify(new AchievementUnlockedNotification($achievement));
                broadcast(new NewNotification($this->id, [
                    'type' => 'achievement_unlocked',
                    'title' => 'Achievement unlocked!',
                    'body' => 'You unlocked "' . $achievement->name . '"',
                    'url' => '/achievements',
                ]));

                // Auto-grant linked award if one exists
                $linkedAward = Award::where('achievement_id', $achievement->id)->first();
                if ($linkedAward && ! UserAward::where('user_id', $this->id)->where('award_id', $linkedAward->id)->exists()) {
                    UserAward::create([
                        'user_id' => $this->id,
                        'award_id' => $linkedAward->id,
                        'granted_by' => null,
                        'reason' => 'Achievement: ' . $achievement->name,
                    ]);
                }
            }

            $userAchievement->save();
        }
    }
}
