<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Forum;
use Illuminate\Database\Seeder;

class DefaultContentSeeder extends Seeder
{
    public function run(): void
    {
        // Category 1: General
        $general = Category::firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'description' => 'General community discussion',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Forum::firstOrCreate(
            ['slug' => 'general-chat'],
            [
                'category_id' => $general->id,
                'name' => 'General Chat',
                'description' => 'Talk about anything',
                'icon' => 'fa-solid fa-comments',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Forum::firstOrCreate(
            ['slug' => 'introductions'],
            [
                'category_id' => $general->id,
                'name' => 'Introductions',
                'description' => 'Introduce yourself to the community',
                'icon' => 'fa-solid fa-hand-wave',
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        // Category 2: Support
        $support = Category::firstOrCreate(
            ['slug' => 'support'],
            [
                'name' => 'Support',
                'description' => 'Get help from the community',
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        Forum::firstOrCreate(
            ['slug' => 'help-and-support'],
            [
                'category_id' => $support->id,
                'name' => 'Help & Support',
                'description' => 'Ask questions and get help',
                'icon' => 'fa-solid fa-circle-question',
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Forum::firstOrCreate(
            ['slug' => 'bug-reports'],
            [
                'category_id' => $support->id,
                'name' => 'Bug Reports',
                'description' => 'Report issues and bugs',
                'icon' => 'fa-solid fa-bug',
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        // Category 3: Off Topic
        $offTopic = Category::firstOrCreate(
            ['slug' => 'off-topic'],
            [
                'name' => 'Off Topic',
                'description' => 'Non-related discussions',
                'display_order' => 3,
                'is_active' => true,
            ]
        );

        Forum::firstOrCreate(
            ['slug' => 'off-topic-chat'],
            [
                'category_id' => $offTopic->id,
                'name' => 'Off Topic',
                'description' => 'Anything that doesn\'t fit elsewhere',
                'icon' => 'fa-solid fa-mug-hot',
                'display_order' => 1,
                'is_active' => true,
            ]
        );
    }
}
