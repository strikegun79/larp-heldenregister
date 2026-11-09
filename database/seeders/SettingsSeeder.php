<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Standardwerte für Portal-Einstellungen (ADM-09).
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'association_name' => 'Waldritter-Gießen e.V.',
            'contact_email' => 'info@waldritter-giessen.de',
            'portal_logo' => 'Waldritter-Logo2_mit_Untertitel.png',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
