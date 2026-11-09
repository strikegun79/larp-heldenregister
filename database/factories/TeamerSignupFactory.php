<?php

namespace Database\Factories;

use App\Models\Adventure;
use App\Models\TeamerSignup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamerSignup>
 */
class TeamerSignupFactory extends Factory
{
    protected $model = TeamerSignup::class;

    public function definition(): array
    {
        return [
            'adventure_id' => Adventure::factory(),
            'user_id' => User::factory(),
            'teamer_role' => null,
            'agb' => true,
        ];
    }
}
