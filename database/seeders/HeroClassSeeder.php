<?php

namespace Database\Seeders;

use App\Models\HeroClass;
use Illuminate\Database\Seeder;

class HeroClassSeeder extends Seeder
{
    /**
     * Heldenklassen aus dem Legacy-System (type_classes). IDs erhalten.
     */
    public function run(): void
    {
        $classes = [
            ['id' => 1, 'slug' => 'warrior',   'name' => 'Krieger'],
            ['id' => 2, 'slug' => 'ranger',    'name' => 'Waldläufer'],
            ['id' => 3, 'slug' => 'wizard',    'name' => 'Magier'],
            ['id' => 4, 'slug' => 'healer',    'name' => 'Heiler'],
            ['id' => 5, 'slug' => 'alchemist', 'name' => 'Alchemist'],
        ];

        foreach ($classes as $class) {
            HeroClass::updateOrCreate(['id' => $class['id']], $class);
        }
    }
}
