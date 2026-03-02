<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Forum;
use App\Models\Subforum;
use Illuminate\Database\Seeder;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        // Minecraft Support
        $mcSupport = Category::where('slug', 'minecraft-support')->first();
        $pluginHelp = Forum::create([
            'category_id' => $mcSupport->id,
            'name' => 'Plugin Help',
            'slug' => 'plugin-help',
            'description' => 'Get help with plugin issues and configuration',
            'icon' => 'fa-solid fa-plug',
            'display_order' => 1,
        ]);

        Forum::create([
            'category_id' => $mcSupport->id,
            'name' => 'Server Issues',
            'slug' => 'server-issues',
            'description' => 'Report and troubleshoot server problems',
            'icon' => 'fa-solid fa-desktop',
            'display_order' => 2,
        ]);

        // Minecraft Releases
        $mcReleases = Category::where('slug', 'minecraft-releases')->first();
        Forum::create([
            'category_id' => $mcReleases->id,
            'name' => 'Plugins',
            'slug' => 'plugins',
            'description' => 'Share and download community plugins',
            'icon' => 'fa-solid fa-box',
            'display_order' => 1,
        ]);

        Forum::create([
            'category_id' => $mcReleases->id,
            'name' => 'Configs',
            'slug' => 'configs',
            'description' => 'Share server configurations and setups',
            'icon' => 'fa-solid fa-gear',
            'display_order' => 2,
        ]);

        // Minecraft General
        $mcGeneral = Category::where('slug', 'minecraft-general')->first();
        Forum::create([
            'category_id' => $mcGeneral->id,
            'name' => 'Off Topic',
            'slug' => 'mc-off-topic',
            'description' => 'Chat about anything Minecraft related',
            'icon' => 'fa-solid fa-comments',
            'display_order' => 1,
        ]);

        Forum::create([
            'category_id' => $mcGeneral->id,
            'name' => 'Introductions',
            'slug' => 'mc-introductions',
            'description' => 'Introduce yourself to the community',
            'icon' => 'fa-solid fa-hand-wave',
            'display_order' => 2,
        ]);

        // Rust Support
        $rustSupport = Category::where('slug', 'rust-support')->first();
        Forum::create([
            'category_id' => $rustSupport->id,
            'name' => 'Plugin Help',
            'slug' => 'rust-plugin-help',
            'description' => 'Get help with Rust plugins',
            'icon' => 'fa-solid fa-plug',
            'display_order' => 1,
        ]);

        Forum::create([
            'category_id' => $rustSupport->id,
            'name' => 'Server Issues',
            'slug' => 'rust-server-issues',
            'description' => 'Troubleshoot Rust server problems',
            'icon' => 'fa-solid fa-desktop',
            'display_order' => 2,
        ]);

        // Rust General
        $rustGeneral = Category::where('slug', 'rust-general')->first();
        Forum::create([
            'category_id' => $rustGeneral->id,
            'name' => 'General Chat',
            'slug' => 'rust-general-chat',
            'description' => 'General Rust discussion',
            'icon' => 'fa-solid fa-comments',
            'display_order' => 1,
        ]);

        // Subforums for Plugin Help
        Subforum::create([
            'forum_id' => $pluginHelp->id,
            'name' => 'Spigot Plugins',
            'slug' => 'spigot-plugins',
            'description' => 'Help with Spigot/Bukkit plugins',
            'display_order' => 1,
        ]);

        Subforum::create([
            'forum_id' => $pluginHelp->id,
            'name' => 'Paper Plugins',
            'slug' => 'paper-plugins',
            'description' => 'Help with Paper-specific plugins',
            'display_order' => 2,
        ]);
    }
}
