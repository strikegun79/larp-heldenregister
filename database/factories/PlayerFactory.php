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
            'name'                     => fake('de_DE')->firstName(),
            'lastname'                 => fake('de_DE')->lastName(),
            'email'                    => fake()->safeEmail(),
            'dayofbirth'               => fake()->dateTimeBetween('-30 years', '-18 years')->format('Y-m-d'),
            'gender'                   => fake()->randomElement(['männlich', 'weiblich', 'divers']),
            'active'                   => true,
            'address_same_as_guardian' => true,
        ];
    }

    /** Minderjähriger Spieler (6–17 Jahre). */
    public function minor(): static
    {
        return $this->state(fn () => [
            'dayofbirth'               => fake()->dateTimeBetween('-17 years', '-6 years')->format('Y-m-d'),
            'address_same_as_guardian' => true,
        ]);
    }

    /** Inaktiver Spieler. */
    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
