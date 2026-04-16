<?php

namespace Database\Factories;

use App\Models\Poll;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poll>
 */
class PollFactory extends Factory
{
    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'question' => rtrim(fake()->sentence(6), '.').'?',
            'allow_multiple' => false,
            'closes_at' => null,
        ];
    }
}
