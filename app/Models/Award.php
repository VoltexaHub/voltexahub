<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Award extends Model
{
    protected $fillable = ['name', 'description', 'icon', 'icon_path'];

    protected $appends = ['icon_url'];

    protected function iconUrl(): Attribute
    {
        return Attribute::get(fn () => $this->icon_path
            ? Storage::disk('public')->url($this->icon_path)
            : null
        );
    }

    public function userAwards(): HasMany
    {
        return $this->hasMany(UserAward::class);
    }
}
