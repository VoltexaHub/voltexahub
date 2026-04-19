<?php

namespace Database\Seeders;

use App\Forum\Models\Category;
use App\Forum\Models\Forum;
use App\Models\Group;
use App\Models\User;
use App\Settings\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Groups
        $admins = Group::firstOrCreate(['name' => 'Administrators'], [
            'color' => '#7c3aed',
            'icon' => '⚡',
            'is_staff' => true,
            'display_order' => 0,
            'permissions' => [
                'is_admin' => true,
                'is_moderator' => true,
                'can_post' => true,
                'can_create_thread' => true,
                'can_upload_avatar' => true,
                'can_use_signature' => true,
                'can_react' => true,
            ],
        ]);

        $mods = Group::firstOrCreate(['name' => 'Moderators'], [
            'color' => '#2563eb',
            'icon' => '🛡️',
            'is_staff' => true,
            'display_order' => 1,
            'permissions' => [
                'is_admin' => false,
                'is_moderator' => true,
                'can_post' => true,
                'can_create_thread' => true,
                'can_upload_avatar' => true,
                'can_use_signature' => true,
                'can_react' => true,
            ],
        ]);

        Group::firstOrCreate(['name' => 'Members'], [
            'color' => '#94a3b8',
            'icon' => null,
            'is_staff' => false,
            'display_order' => 2,
            'permissions' => [
                'is_admin' => false,
                'is_moderator' => false,
                'can_post' => true,
                'can_create_thread' => true,
                'can_upload_avatar' => true,
                'can_use_signature' => false,
                'can_react' => true,
            ],
        ]);

        Group::firstOrCreate(['name' => 'Newbies'], [
            'color' => '#64748b',
            'icon' => null,
            'is_staff' => false,
            'display_order' => 3,
            'permissions' => [
                'is_admin' => false,
                'is_moderator' => false,
                'can_post' => true,
                'can_create_thread' => false,
                'can_upload_avatar' => false,
                'can_use_signature' => false,
                'can_react' => true,
            ],
        ]);

        // Default settings
        Setting::set('site_name', 'VoltexaHub');
        Setting::set('site_tagline', 'Tech forum for developers & sysadmins');

        // Sample categories + forums (skip if already exist)
        if (Category::count() === 0) {
            $general = Category::create(['name' => 'General', 'display_order' => 0]);
            Forum::create([
                'category_id' => $general->id,
                'name' => 'Introductions',
                'description' => 'New here? Say hello.',
                'display_order' => 0,
            ]);
            Forum::create([
                'category_id' => $general->id,
                'name' => 'General Discussion',
                'description' => 'Anything goes.',
                'display_order' => 1,
            ]);

            $tech = Category::create(['name' => 'Technology', 'display_order' => 1]);
            Forum::create([
                'category_id' => $tech->id,
                'name' => 'Web Development',
                'description' => 'Frontend, backend, APIs, frameworks.',
                'display_order' => 0,
            ]);
            Forum::create([
                'category_id' => $tech->id,
                'name' => 'Servers & DevOps',
                'description' => 'Linux, Docker, CI/CD, cloud.',
                'display_order' => 1,
            ]);
            Forum::create([
                'category_id' => $tech->id,
                'name' => 'Security',
                'description' => 'Pen testing, hardening, CVEs.',
                'display_order' => 2,
            ]);
        }
    }
}
