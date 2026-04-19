<?php
namespace App\Forum\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = ['thread_id', 'user_id', 'body', 'is_deleted', 'edited_at', 'edited_by_id'];
    protected $casts = ['is_deleted' => 'boolean', 'edited_at' => 'datetime'];

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn($q) => $q->where('is_deleted', false));
    }

    public function thread(): BelongsTo { return $this->belongsTo(Thread::class); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function reactions(): HasMany { return $this->hasMany(PostReaction::class); }
}
