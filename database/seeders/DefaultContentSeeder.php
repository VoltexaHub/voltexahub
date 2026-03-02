<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Forum;
use App\Models\Game;
use Illuminate\Database\Seeder;

class DefaultContentSeeder extends Seeder
{
    public function run(): void
    {
        $game = Game::firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'icon' => 'fa-solid fa-globe',
                'display_order' => 0,
                'is_active' => true,
            ]
        );

        $category = Category::firstOrCreate(
            ['slug' => 'general-discussion'],
            [
                'game_id' => $game->id,
                'name' => 'General',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Forum::firstOrCreate(
            ['slug' => 'general-chat'],
            [
                'category_id' => $category->id,
                'name' => 'General Chat',
                'description' => 'General community discussion',
                'icon' => 'fa-solid fa-comments',
                'display_order' => 1,
                'is_active' => true,
            ]
        );
    }
}
