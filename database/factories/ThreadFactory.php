<?php

namespace Database\Factories;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'forum_id' => Forum::factory(),
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(5),
            'is_pinned' => false,
            'is_locked' => false,
            'views_count' => fake()->numberBetween(0, 500),
        ];
    }
}
