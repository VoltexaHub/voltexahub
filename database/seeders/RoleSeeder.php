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
        $member->update(['priority' => 10, 'is_staff' => false, 'staff_permissions' => []]);

        $vip = Role::firstOrCreate(['name' => 'vip']);
        $vip->syncPermissions(['create-threads', 'create-posts', 'react']);
        $vip->update(['priority' => 10, 'is_staff' => false, 'staff_permissions' => []]);

        $elite = Role::firstOrCreate(['name' => 'elite']);
        $elite->syncPermissions(['create-threads', 'create-posts', 'react']);
        $elite->update(['priority' => 10, 'is_staff' => false, 'staff_permissions' => []]);

        $moderator = Role::firstOrCreate(['name' => 'moderator']);
        $moderator->syncPermissions([
            'create-threads', 'create-posts', 'react',
            'delete-posts', 'pin-threads', 'lock-threads', 'solve-threads',
        ]);
        $moderator->update([
            'priority' => 50,
            'is_staff' => true,
            'staff_permissions' => ['view_reports', 'manage_threads', 'manage_posts', 'ban_users', 'grant_awards'],
        ]);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());
        $admin->update([
            'priority' => 100,
            'is_staff' => true,
            'staff_permissions' => ['view_reports', 'manage_threads', 'manage_posts', 'ban_users', 'grant_awards'],
        ]);
    }
}
