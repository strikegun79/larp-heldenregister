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
            'player_id' => Player::factory(),
            'character_name' => fake()->firstName(),
            'homeplace' => fake()->city(),
            'born' => fake()->date(),
            'active' => true,
        ];
    }
}
