<?php

namespace Database\Seeders;

use App\Models\ForumConfig;
use Illuminate\Database\Seeder;

class ForumConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            'forum_name' => 'My Forum',
            'multi_game_mode' => 'false',
            'accent_color' => '#7c3aed',
            'rcon_host_minecraft' => '',
            'rcon_port_minecraft' => '25575',
            'rcon_password_minecraft' => '',
            'rcon_host_rust' => '',
            'rcon_port_rust' => '28016',
            'rcon_password_rust' => '',
        ];

        foreach ($configs as $key => $value) {
            ForumConfig::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
