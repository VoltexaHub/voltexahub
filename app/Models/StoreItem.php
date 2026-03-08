<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreItem extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'category',
        'price_money', 'price_credits', 'supports_both',
        'item_type', 'item_value', 'minecraft_group', 'game_id', 'is_active', 'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price_money' => 'decimal:2',
            'supports_both' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(StorePurchase::class);
    }
}
