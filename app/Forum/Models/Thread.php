<?php
namespace App\Forum\Models;

use Database\Factories\ThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Thread extends Model
{
    /** @use HasFactory<ThreadFactory> */
    use HasFactory;

    protected $fillable = ['forum_id', 'user_id', 'title', 'slug', 'is_pinned', 'is_locked', 'is_deleted'];
    protected $casts = ['is_pinned' => 'boolean', 'is_locked' => 'boolean', 'is_deleted' => 'boolean'];

    protected static function newFactory(): ThreadFactory
    {
        return ThreadFactory::new();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn($q) => $q->where('is_deleted', false));

        static::creating(function (Thread $thread) {
            if (empty($thread->slug)) {
                $thread->slug = Str::slug($thread->title) . '-' . Str::random(6);
            }
        });
    }

    public function forum(): BelongsTo { return $this->belongsTo(Forum::class); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function posts(): HasMany { return $this->hasMany(Post::class)->where('is_deleted', false)->oldest(); }
    public function lastPost(): BelongsTo { return $this->belongsTo(Post::class, 'last_post_id'); }
}
