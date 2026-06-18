<?php

namespace Database\Seeders;

use App\Models\PerlColor;
use Illuminate\Database\Seeder;

class PerlColorSeeder extends Seeder
{
    /**
     * Perlenfarben aus dem Legacy-System (type_perlcolor).
     * `code` ist der Legacy-Schlüssel (Hex oder 'keine').
     */
    public function run(): void
    {
        $colors = [
            ['code' => '#000000', 'name' => 'schwarz'],
            ['code' => '#0000FF', 'name' => 'blau'],
            ['code' => '#228B22', 'name' => 'grün'],
            ['code' => '#30D5C8', 'name' => 'turkis'],
            ['code' => '#800080', 'name' => 'lila'],
            ['code' => '#8B4513', 'name' => 'braun'],
            ['code' => '#C00000', 'name' => 'rot'],
            ['code' => '#C0C0C0', 'name' => 'silber'],
            ['code' => '#D4AF37', 'name' => 'gold'],
            ['code' => '#F5A633', 'name' => 'orange'],
            ['code' => '#F5F5DC', 'name' => 'beige'],
            ['code' => '#FF69B4', 'name' => 'rosa'],
            ['code' => '#FFFF00', 'name' => 'gelb'],
            ['code' => '#FFFFFF', 'name' => 'weiß'],
            ['code' => 'keine',   'name' => 'keine'],
        ];

        foreach ($colors as $color) {
            PerlColor::updateOrCreate(['code' => $color['code']], $color);
        }
    }
}
