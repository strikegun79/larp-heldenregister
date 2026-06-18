<?php

namespace Database\Factories;

use App\Models\EventStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Adventure>
 */
class AdventureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'location_id' => null,
            'start_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'end_at' => fake()->dateTimeBetween('+2 months', '+3 months'),
            'loot_ep_day' => fake()->numberBetween(0, 5),
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
            'event_client_id' => 1,
            'event_category_id' => 0,
            'max_player' => fake()->numberBetween(5, 30),
            'waitlist' => 0,
            'fee' => 12,
        ];
    }

    /** Anmeldung geschlossen. */
    public function registrationClosed(): static
    {
        return $this->state(fn () => ['event_status_id' => 40]);
    }
}
