<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake('de_DE')->words(nb: 2, asText: true),
            'description' => fake('de_DE')->sentence(),
            'image' => null,
        ];
    }
}
