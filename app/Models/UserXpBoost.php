<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserXpBoost extends Model
{
    protected $fillable = ['user_id', 'multiplier', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'multiplier' => 'float',
        ];
    }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
