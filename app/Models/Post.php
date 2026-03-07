<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'thread_id', 'user_id', 'body', 'is_first_post', 'reaction_count',
        'edited_at', 'edit_count',
    ];

    protected $appends = ['is_edited', 'author', 'rendered_content'];

    protected function casts(): array
    {
        return [
            'is_first_post' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    protected function isEdited(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => $this->edited_at !== null
        );
    }

    public function getAuthorAttribute()
    {
        if (!$this->relationLoaded('user') || !$this->user) {
            return null;
        }
        $user = $this->user;
        $level = \App\Services\XpService::levelFor($user->xp ?? 0);
        $user->level = $level?->level;
        $user->level_label = $level?->label;
        $activeBoost = \App\Models\UserXpBoost::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();
        $user->xp_boost_active = $activeBoost ? true : false;
        return $user;
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

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }
}
