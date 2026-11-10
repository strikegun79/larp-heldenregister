<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hero>
 */
class HeroFactory extends Factory
{
    public function definition(): array
    {
        return [
            'player_id'         => Player::factory(),
            'character_name'    => fake('de_DE')->firstName().' '.fake()->randomElement(['von', 'aus', 'der']),
            'homeplace'         => fake('de_DE')->city(),
            'born'              => fake()->dateTimeBetween('-300 years', '-15 years')->format('Y-m-d'),
            'active'            => true,
            'public_visible'    => true,
            'public_searchable' => true,
        ];
    }

    /** Inaktiver (verstorbener/ruhender) Held. */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
            'died'   => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
        ]);
    }

    /** Held ohne öffentliche Sichtbarkeit. */
    public function private(): static
    {
        return $this->state(fn () => [
            'public_visible'    => false,
            'public_searchable' => false,
        ]);
    }
}
