<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Forum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Forum>
 */
class ForumFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.Str::random(5),
            'description' => fake()->sentence(),
            'position' => 0,
        ];
    }
}
