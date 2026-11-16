<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Stammdaten einspielen. Alle Seeder arbeiten mit firstOrCreate: Sie legen nur
     * fehlende Einträge an und überschreiben keine Änderungen aus der Administration.
     * Geänderte Standardwerte für bestehende Datenbanken → per Migration ausrollen.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            HeroClassSeeder::class,
            PerlColorSeeder::class,
            EpTransactionTypeSeeder::class,
            LocationSeeder::class,
            EventLookupSeeder::class,
            SettingsSeeder::class,
            TaskTypeDefinitionSeeder::class,
        ]);
    }
}
