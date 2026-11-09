<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Veranstaltungsorte aus dem Legacy-System (location). IDs erhalten.
     */
    public function run(): void
    {
        $locations = [
            ['id' => 1, 'titel' => 'Solmser Pfadfinder Lagerplatz', 'plz' => '35321', 'city' => 'Laubach'],
            ['id' => 2, 'titel' => 'Lollar Grillhütte',            'city' => 'Lollar'],
            ['id' => 3, 'titel' => 'Burg Staufenberg',             'city' => 'Staufenberg'],
            ['id' => 4, 'titel' => 'Kloster Arnsburg',             'city' => 'Lich'],
            ['id' => 5, 'titel' => 'Waldsportplatz Beuern-Buseck', 'city' => 'Beuern-Buseck'],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(['id' => $location['id']], $location);
        }
    }
}
