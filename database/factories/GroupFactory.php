<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'color' => '#7c3aed',
            'is_staff' => false,
            'permissions' => ['can_post' => true, 'can_create_thread' => true, 'is_admin' => false, 'is_moderator' => false],
            'display_order' => 0,
        ];
    }
}
