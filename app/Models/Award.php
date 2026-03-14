<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Award extends Model
{
    protected $fillable = [
        'name', 'description', 'icon', 'icon_path',
        'type', 'achievement_id', 'price_credits', 'price_money', 'display_order',
    ];

    protected $appends = ['icon_url', 'holder_count'];

    protected $casts = [
        'price_money' => 'decimal:2',
        'achievement_id' => 'integer',
    ];

    protected function iconUrl(): Attribute
    {
        return Attribute::get(fn () => $this->icon_path
            ? Storage::disk('public')->url($this->icon_path)
            : null
        );
    }

    protected function holderCount(): Attribute
    {
        return Attribute::get(fn () => $this->userAwards()->count());
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function userAwards(): HasMany
    {
        return $this->hasMany(UserAward::class);
    }
}
