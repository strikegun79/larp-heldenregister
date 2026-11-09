<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ROLE-05: Projektleitungs-Rechte End-to-End verifizieren.
 *
 * Projektleitung (ID 30) hat: heldenregister.view, events.view, events.edit,
 * adventure.book/modify/cancel – aber kein portal.manage und kein heldenregister.edit.
 */
class ProjectleadRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function projektleitung(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(30);

        return $user;
    }

    public function test_dashboard_zeigt_heldenregister_und_abenteuer_aber_keine_verwaltung(): void
    {
        $this->actingAs($this->projektleitung())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Heldenregister')
            ->assertSee('Abenteuer')
            // "Portal-Administration" ist der eindeutige Untertitel der Admin-Karte.
            ->assertDontSee('Portal-Administration');
    }

    public function test_projektleitung_kann_heldenregister_einsehen(): void
    {
        Hero::factory()->create(['character_name' => 'Thrandir der Weise']);

        $this->actingAs($this->projektleitung())
            ->get(route('heroes.index'))
            ->assertOk()
            ->assertSee('Thrandir der Weise');
    }

    public function test_projektleitung_kann_events_anlegen_und_bearbeiten(): void
    {
        $pl = $this->projektleitung();

        // Event-Erstellung-Formular aufrufbar.
        $this->actingAs($pl)
            ->get(route('adventures.create'))
            ->assertOk();

        // Event anlegen.
        $this->actingAs($pl)
            ->post(route('adventures.store'), [
                'name' => 'Frühlingsturnier Waldritter',
                'start_at' => '2026-09-01 10:00',
                'end_at' => '2026-09-03 18:00',
                'event_status_id' => 30,
                'event_client_id' => 1,
                'event_category_id' => 0,
                'max_player' => 30,
                'fee' => 15,
            ])
            ->assertRedirect();

        $adventure = Adventure::firstWhere('name', 'Frühlingsturnier Waldritter');
        $this->assertNotNull($adventure);

        // Event bearbeiten (AJAX → Modal-Partial).
        $this->actingAs($pl)
            ->get(route('adventures.edit', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();
    }

    public function test_projektleitung_kann_abenteuer_buchen(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 10]);
        $player = Player::factory()->create();
        $pl = $this->projektleitung();
        $pl->players()->attach($player->id, ['self' => true]);

        $this->actingAs($pl)
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
                'kontakt_telefon' => '+49 641 123456',
            ])
            ->assertSessionHas('status');
    }

    public function test_projektleitung_kann_portal_nicht_verwalten(): void
    {
        $this->actingAs($this->projektleitung())
            ->get(route('admin.index'))
            ->assertForbidden();
    }

    public function test_projektleitung_kann_helden_nicht_bearbeiten(): void
    {
        $hero = Hero::factory()->create();

        // EP buchen erfordert heldenregister.edit – Projektleitung hat das nicht.
        $this->actingAs($this->projektleitung())
            ->post(route('heroes.ep.store', $hero), [
                'amount' => 5,
                'description' => 'Testabenteuer',
                'type_id' => 10,
            ])
            ->assertForbidden();
    }
}
