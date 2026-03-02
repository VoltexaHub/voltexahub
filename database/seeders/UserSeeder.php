<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@voltexahub.com',
            'password' => 'password',
            'user_title' => 'Forum Administrator',
            'bio' => 'Community administrator.',
            'avatar_color' => '#ef4444',
            'credits' => 10000,
            'post_count' => 150,
            'is_online' => true,
            'last_active_at' => now(),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Moderator
        $mod = User::create([
            'name' => 'ModeratorSam',
            'username' => 'modsam',
            'email' => 'mod@voltexahub.com',
            'password' => 'password',
            'user_title' => 'Community Moderator',
            'bio' => 'Keeping the forums clean and friendly!',
            'avatar_color' => '#3b82f6',
            'credits' => 5000,
            'post_count' => 85,
            'last_active_at' => now()->subHours(2),
            'discord_username' => 'modsam#1234',
            'email_verified_at' => now(),
        ]);
        $mod->assignRole('moderator');

        // VIP member
        $vip = User::create([
            'name' => 'GamerPro',
            'username' => 'gamerpro',
            'email' => 'vip@voltexahub.com',
            'password' => 'password',
            'user_title' => 'VIP Member',
            'bio' => 'Minecraft enthusiast and plugin developer.',
            'avatar_color' => '#f59e0b',
            'credits' => 2500,
            'post_count' => 200,
            'last_active_at' => now()->subMinutes(30),
            'minecraft_ign' => 'GamerPro_MC',
            'minecraft_verified' => true,
            'email_verified_at' => now(),
        ]);
        $vip->assignRole('vip');

        // Elite member
        $elite = User::create([
            'name' => 'ElitePlayer',
            'username' => 'eliteplayer',
            'email' => 'elite@voltexahub.com',
            'password' => 'password',
            'user_title' => 'Elite Supporter',
            'bio' => 'Supporting the community since day one.',
            'avatar_color' => '#8b5cf6',
            'credits' => 7500,
            'post_count' => 320,
            'last_active_at' => now()->subHours(1),
            'discord_username' => 'eliteplayer',
            'twitter_handle' => '@eliteplayer',
            'website_url' => 'https://eliteplayer.dev',
            'email_verified_at' => now(),
        ]);
        $elite->assignRole('elite');

        // Regular member
        $member = User::create([
            'name' => 'NewPlayer',
            'username' => 'newplayer',
            'email' => 'member@voltexahub.com',
            'password' => 'password',
            'bio' => 'Just joined the community!',
            'avatar_color' => '#10b981',
            'credits' => 50,
            'post_count' => 3,
            'last_active_at' => now()->subDays(1),
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');
    }
}
