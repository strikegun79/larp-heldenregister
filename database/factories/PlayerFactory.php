<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'dayofbirth' => fake()->date(),
            'gender' => fake()->randomElement(['männlich', 'weiblich', 'divers']),
            'active' => true,
            'address_same_as_guardian' => true,
        ];
    }
}
