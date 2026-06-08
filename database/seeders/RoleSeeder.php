<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Rollen aus dem Legacy-System (type_role). IDs bleiben erhalten,
     * damit die spätere ETL-Migration der user2role-Zuordnungen passt.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 0,  'slug' => 'none',          'label' => 'keine'],
            ['id' => 10, 'slug' => 'admin',         'label' => 'Admin'],
            ['id' => 20, 'slug' => 'registrar',     'label' => 'Registrar'],
            ['id' => 30, 'slug' => 'project_lead',  'label' => 'Projektleiter'],
            ['id' => 40, 'slug' => 'game_master',   'label' => 'Spielleiter'],
            ['id' => 50, 'slug' => 'teamer',        'label' => 'Teamer'],
            ['id' => 60, 'slug' => 'event_booking', 'label' => 'Event buchen'],
            ['id' => 70, 'slug' => 'participant',   'label' => 'Teilnehmer'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['id' => $role['id']], $role);
        }
    }
}
