<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $casts = [
        'perks'             => 'array',
        'staff_permissions' => 'array',
        'is_staff'          => 'boolean',
        'priority'          => 'integer',
    ];
}
