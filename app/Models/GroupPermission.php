<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupPermission extends Model
{
    protected $fillable = ['role_name', 'can_view', 'can_post', 'can_reply'];

    protected $casts = [
        'can_view'  => 'boolean',
        'can_post'  => 'boolean',
        'can_reply' => 'boolean',
    ];
}
