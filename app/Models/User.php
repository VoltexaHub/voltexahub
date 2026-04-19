<?php

namespace App\Models;

use App\Forum\Models\Thread as ForumThread;
use App\Forum\Models\Post as ForumPost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'email', 'password', 'group_id', 'avatar', 'bio',
        'signature', 'is_trusted', 'credits', 'post_count', 'thread_count',
        'last_seen_at', 'banned_at', 'banned_reason', 'referral_code', 'referred_by_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'banned_at' => 'datetime',
        'is_trusted' => 'boolean',
    ];

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function threads(): HasMany { return $this->hasMany(ForumThread::class); }
    public function posts(): HasMany { return $this->hasMany(ForumPost::class); }
    public function isBanned(): bool { return $this->banned_at !== null; }
    public function isAdmin(): bool { return $this->group?->can('is_admin') ?? false; }
    public function isModerator(): bool { return $this->group?->can('is_moderator') ?? false; }
}
