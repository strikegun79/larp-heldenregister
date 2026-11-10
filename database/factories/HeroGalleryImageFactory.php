<?php

namespace Database\Factories;

use App\Models\Hero;
use App\Models\HeroGalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HeroGalleryImageFactory extends Factory
{
    protected $model = HeroGalleryImage::class;

    public function definition(): array
    {
        return [
            'hero_id'    => Hero::factory(),
            'path'       => 'heroes/gallery/'.Str::uuid().'.jpg',
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
