<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
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
