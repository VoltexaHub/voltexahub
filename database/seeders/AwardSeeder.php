<?php

namespace Database\Seeders;

use App\Models\Award;
use Illuminate\Database\Seeder;

class AwardSeeder extends Seeder
{
    public function run(): void
    {
        $awards = [
            [
                'name' => 'Community Champion',
                'description' => 'Awarded for outstanding community contributions',
                'icon' => '🏅',
            ],
            [
                'name' => 'Bug Hunter',
                'description' => 'Found and reported critical bugs',
                'icon' => '🐛',
            ],
            [
                'name' => 'Content Creator',
                'description' => 'Creates exceptional forum content',
                'icon' => '🎨',
            ],
            [
                'name' => 'Helpful Member',
                'description' => 'Consistently helps other members',
                'icon' => '🤗',
            ],
            [
                'name' => 'Top Contributor',
                'description' => 'One of the most active and valuable contributors',
                'icon' => '⭐',
            ],
            [
                'name' => 'Event Winner',
                'description' => 'Won a community event or competition',
                'icon' => '🏆',
            ],
            [
                'name' => 'Early Supporter',
                'description' => 'One of the first members to join',
                'icon' => '🌟',
            ],
            [
                'name' => 'Staff Pick',
                'description' => 'Recognized by staff for excellence',
                'icon' => '👑',
            ],
        ];

        foreach ($awards as $award) {
            Award::create($award);
        }
    }
}
