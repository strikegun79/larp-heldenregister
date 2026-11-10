<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-06: Verwaltungsliste der Events (Status, Belegung, Aktionen) – getrennt
 * von der Browse-Liste.
 */
class EventManageListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_manage_list_shows_status_occupancy_and_actions(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'Burgsturm', 'max_player' => 8]);

        $response = $this->actingAs($this->userWithRole(30)) // Projektleitung
            ->get(route('adventures.manage-index'))
            ->assertOk();

        $response->assertSee('Burgsturm');
        $response->assertSee('/ 8');                                  // Belegung
        $response->assertSee('Anmeldung offen');                      // Status-Badge
        $response->assertSee(route('adventures.manage', $adventure), false); // Verwalten
        $response->assertSee(route('adventures.cancel', $adventure), false); // Absagen
        $response->assertSee('Neues Abenteuer');                      // anlegen
    }

    public function test_cancelled_event_has_no_cancel_action(): void
    {
        $adventure = Adventure::factory()->create(['event_status_id' => 70]); // abgesagt

        $this->actingAs($this->userWithRole(30))
            ->get(route('adventures.manage-index'))
            ->assertOk()
            ->assertDontSee(route('adventures.cancel', $adventure), false);
    }

    public function test_browse_list_has_no_management_create_button(): void
    {
        Adventure::factory()->create();

        // Browse-Liste (auch für Verwalter) zeigt keinen „Neues Abenteuer"-Button,
        // aber den Link zur Verwaltung.
        $this->actingAs($this->userWithRole(30))
            ->get(route('adventures.index'))
            ->assertOk()
            ->assertDontSee('Neues Abenteuer')
            ->assertSee('Verwaltung');
    }
}
