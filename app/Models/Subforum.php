<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subforum extends Model
{
    protected $fillable = [
        'forum_id', 'name', 'slug', 'description',
        'display_order', 'is_active', 'thread_count', 'post_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }
}
