<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'create-threads',
            'create-posts',
            'react',
            'delete-posts',
            'pin-threads',
            'lock-threads',
            'solve-threads',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles with permissions
        $member = Role::firstOrCreate(['name' => 'member']);
        $member->syncPermissions(['create-threads', 'create-posts', 'react']);

        $vip = Role::firstOrCreate(['name' => 'vip']);
        $vip->syncPermissions(['create-threads', 'create-posts', 'react']);

        $elite = Role::firstOrCreate(['name' => 'elite']);
        $elite->syncPermissions(['create-threads', 'create-posts', 'react']);

        $moderator = Role::firstOrCreate(['name' => 'moderator']);
        $moderator->syncPermissions([
            'create-threads', 'create-posts', 'react',
            'delete-posts', 'pin-threads', 'lock-threads', 'solve-threads',
        ]);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());
    }
}
