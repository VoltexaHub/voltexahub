<?php

namespace Database\Factories;

use App\Forum\Models\Forum;
use App\Forum\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'forum_id' => Forum::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'is_pinned' => false,
            'is_locked' => false,
            'is_deleted' => false,
        ];
    }
}
