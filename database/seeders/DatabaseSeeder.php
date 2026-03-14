<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultContentSeeder::class,
            RoleSeeder::class,
            ForumConfigSeeder::class,
            CategorySeeder::class,
            ForumSeeder::class,
            AchievementSeeder::class,
            AwardSeeder::class,
            StoreItemSeeder::class,
            UserSeeder::class,
        ]);
    }
}
