<?php

namespace Database\Factories;

use App\Models\HeroClass;
use App\Models\PerlColor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 *
 * Setzt voraus, dass HeroClassSeeder und PerlColorSeeder bereits ausgeführt wurden.
 * In Tests: $this->seed([HeroClassSeeder::class, PerlColorSeeder::class]);
 */
class SkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'          => $this->faker->unique()->words(2, true),
            'description'   => $this->faker->sentence(),
            'ep_costs'      => $this->faker->numberBetween(0, 10),
            'level'         => $this->faker->numberBetween(1, 6),
            'hero_class_id' => HeroClass::inRandomOrder()->value('id') ?? 1,
            'perl_color_id' => PerlColor::inRandomOrder()->value('id'),
            'perl_count'    => $this->faker->numberBetween(1, 5),
        ];
    }
}
