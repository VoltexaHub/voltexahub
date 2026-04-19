<?php

namespace Database\Factories;

use App\Forum\Models\Category;
use App\Forum\Models\Forum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Forum>
 */
class ForumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'display_order' => 0,
            'icon' => null,
        ];
    }
}
