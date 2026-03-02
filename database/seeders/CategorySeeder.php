<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $minecraft = Game::where('slug', 'minecraft')->first();
        $rust = Game::where('slug', 'rust')->first();

        // Minecraft categories
        Category::create([
            'game_id' => $minecraft->id,
            'name' => 'Support',
            'slug' => 'minecraft-support',
            'description' => 'Get help with Minecraft-related issues',
            'display_order' => 1,
        ]);

        Category::create([
            'game_id' => $minecraft->id,
            'name' => 'Releases',
            'slug' => 'minecraft-releases',
            'description' => 'Plugin and config releases',
            'display_order' => 2,
        ]);

        Category::create([
            'game_id' => $minecraft->id,
            'name' => 'General Discussion',
            'slug' => 'minecraft-general',
            'description' => 'General Minecraft discussion',
            'display_order' => 3,
        ]);

        // Rust categories
        Category::create([
            'game_id' => $rust->id,
            'name' => 'Support',
            'slug' => 'rust-support',
            'description' => 'Get help with Rust-related issues',
            'display_order' => 1,
        ]);

        Category::create([
            'game_id' => $rust->id,
            'name' => 'General',
            'slug' => 'rust-general',
            'description' => 'General Rust discussion',
            'display_order' => 2,
        ]);
    }
}
