<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GithubSponsor extends Model
{
    protected $fillable = [
        'github_login',
        'tier',
        'active',
        'sponsored_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sponsored_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
