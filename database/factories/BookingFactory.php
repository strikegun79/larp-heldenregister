<?php

namespace Database\Factories;

use App\Models\Adventure;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'adventure_id' => Adventure::factory(),
            'player_id' => Player::factory(),
            'event_role_id' => 1,
            'agb' => true,
            'waitlisted' => false,
        ];
    }
}
