<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreadPrefix extends Model
{
    protected $fillable = [
        'name', 'color', 'bg_color', 'text_color', 'display_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
