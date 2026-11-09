<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use App\Models\EventClient;
use App\Models\EventRole;
use App\Models\EventStatus;
use Illuminate\Database\Seeder;

class EventLookupSeeder extends Seeder
{
    /**
     * Event-Lookups aus dem Legacy-System (event_category, type_eventStatus,
     * event_auftraggeber, type_event_role). IDs jeweils erhalten.
     */
    public function run(): void
    {
        $categories = [
            ['id' => 0, 'name' => 'Keine Kategorie',        'description' => null],
            ['id' => 2, 'name' => 'Samstagsspiel',          'description' => 'LARP 7-17 Jahren'],
            ['id' => 3, 'name' => 'LARP-Camp',              'description' => 'LARP 7-17 Jahren'],
            ['id' => 4, 'name' => 'Tulderon-Zeltfreizeit',  'description' => 'Jugendliverollenspiel ab 14 Jahren'],
        ];
        foreach ($categories as $row) {
            EventCategory::updateOrCreate(['id' => $row['id']], $row);
        }

        $statuses = [
            ['id' => 0,  'description' => 'unbekannt',             'color' => '#FFFFFF'],
            ['id' => 10, 'description' => 'in Bearbeitung',        'color' => '#ffeb52'],
            ['id' => 20, 'description' => 'geplant',               'color' => '#ffeb52'],
            ['id' => 30, 'description' => 'Anmeldung offen',       'color' => '#a2de00'],
            ['id' => 40, 'description' => 'Anmeldung geschlossen', 'color' => '#a2de00'],
            ['id' => 50, 'description' => 'Abrechnung',            'color' => '#54c8ff'],
            ['id' => 60, 'description' => 'Abgeschlossen',         'color' => '#23d9c9'],
            ['id' => 70, 'description' => 'abgesagt',              'color' => '#f2711c'],
        ];
        foreach ($statuses as $row) {
            EventStatus::updateOrCreate(['id' => $row['id']], $row);
        }

        $clients = [
            ['id' => 1, 'name' => 'Waldritter-Gießen e.V.'],
            ['id' => 2, 'name' => 'Jugendförderung Landkreis Gießen'],
        ];
        foreach ($clients as $row) {
            EventClient::updateOrCreate(['id' => $row['id']], $row);
        }

        $roles = [
            ['id' => 1, 'description' => 'Spieler'],
            ['id' => 2, 'description' => 'NSC Elternteil'],
            ['id' => 3, 'description' => 'Teamer A'],
            ['id' => 4, 'description' => 'Teamer B'],
            ['id' => 5, 'description' => 'Teamer C'],
        ];
        foreach ($roles as $row) {
            EventRole::updateOrCreate(['id' => $row['id']], $row);
        }
    }
}
