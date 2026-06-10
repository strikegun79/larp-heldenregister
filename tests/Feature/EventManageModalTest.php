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
 * ADV-16: Tabs im Event-Modal (Player: Event/Anmeldungen) und
 * Verwaltungs-Modal (Editor/Anmeldungen/Check-in).
 */
class EventManageModalTest extends TestCase
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

    public function test_player_detail_has_event_and_booking_tabs_and_footer(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $response = $this->actingAs($this->userWithRole(60)) // Event buchen
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $response->assertSee('data-tab="event"', false);
        $response->assertSee('data-tab="bookings"', false);
        // Anmelden-Button als Footer-Aktion (Unteransicht).
        $response->assertSee('data-modal-actions', false);
        $response->assertSee(route('adventures.bookings.create', $adventure), false);
    }

    public function test_manage_modal_has_three_tabs_with_editor(): void
    {
        $adventure = Adventure::factory()->create();

        $response = $this->actingAs($this->userWithRole(20)) // Bürokrat: events.edit
            ->get(route('adventures.manage', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $response->assertSee('data-tab="data"', false);
        $response->assertSee('data-tab="bookings"', false);
        $response->assertSee('data-tab="checkin"', false);
        // Tab 1 enthält den Editor (Name-Feld) und KEINE Selbst-Anmeldung.
        $response->assertSee('name="function_email"', false);
        $response->assertSee(route('adventures.update', $adventure), false);
        $response->assertDontSee(route('adventures.bookings.create', $adventure), false);
    }

    public function test_manage_modal_requires_events_edit(): void
    {
        $adventure = Adventure::factory()->create();

        // Spielleiter hat events.view, aber kein events.edit.
        $this->actingAs($this->userWithRole(40))
            ->get(route('adventures.manage', $adventure))
            ->assertForbidden();
    }

    public function test_admin_event_list_links_to_manage_modal(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'Verwaltungs-Event']);

        $this->actingAs($this->userWithRole(10)) // Admin: portal.manage
            ->get(route('admin.adventures.index'))
            ->assertOk()
            ->assertSee('Verwaltungs-Event')
            ->assertSee(route('adventures.manage', $adventure), false);
    }

    public function test_admin_event_list_requires_portal_manage(): void
    {
        // Bürokrat verwaltet Events, hat aber kein portal.manage.
        $this->actingAs($this->userWithRole(20))
            ->get(route('admin.adventures.index'))
            ->assertForbidden();
    }
}
