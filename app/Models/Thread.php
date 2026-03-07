<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Thread extends Model
{
    protected $fillable = [
        'forum_id', 'subforum_id', 'user_id', 'title', 'slug', 'body',
        'is_pinned', 'is_locked', 'is_solved', 'solved_post_id', 'prefix_id',
        'view_count', 'reply_count', 'last_reply_at', 'last_reply_user_id',
    ];

    protected $appends = ['author', 'likes_count', 'rendered_content'];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'is_solved' => 'boolean',
            'last_reply_at' => 'datetime',
        ];
    }

    public function getRenderedContentAttribute(): string
    {
        try {
            $svc = app(\App\Services\TextFormatterService::class);

            return $svc->renderFromText($this->body ?? '');
        } catch (\Throwable) {
            return e($this->body ?? '');
        }
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function subforum(): BelongsTo
    {
        return $this->belongsTo(Subforum::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAuthorAttribute()
    {
        return $this->relationLoaded('user') ? $this->user : null;
    }

    public function lastReplyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reply_user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ThreadLike::class);
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function solvedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'solved_post_id');
    }

    public function prefix(): BelongsTo
    {
        return $this->belongsTo(ThreadPrefix::class, 'prefix_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'thread_tags');
    }

    public function poll(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Poll::class);
    }
}
