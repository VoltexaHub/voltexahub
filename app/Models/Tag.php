<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'use_count'];

    public function threads(): BelongsToMany
    {
        return $this->belongsToMany(Thread::class, 'thread_tags');
    }
}
