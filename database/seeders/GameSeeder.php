<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        Game::create([
            'name' => 'Minecraft',
            'slug' => 'minecraft',
            'icon' => 'fa-solid fa-hammer',
            'description' => 'Minecraft community forums',
            'display_order' => 1,
            'is_active' => true,
        ]);

        Game::create([
            'name' => 'Rust',
            'slug' => 'rust',
            'icon' => 'fa-solid fa-wrench',
            'description' => 'Rust community forums',
            'display_order' => 2,
            'is_active' => true,
        ]);
    }
}
