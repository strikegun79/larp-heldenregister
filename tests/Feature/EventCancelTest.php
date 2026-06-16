<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-07: Event absagen setzt Status „abgesagt" und sperrt neue Anmeldungen.
 */
class EventCancelTest extends TestCase
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

    public function test_events_edit_role_can_cancel_an_event(): void
    {
        $adventure = Adventure::factory()->create(); // Status 30 (Anmeldung offen)

        $this->actingAs($this->userWithRole(30)) // Projektleitung: events.edit
            ->patchJson(route('adventures.cancel', $adventure))
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertSame(EventStatus::CANCELLED, $adventure->fresh()->event_status_id);
    }

    public function test_cancelling_blocks_new_bookings(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $player = Player::factory()->create();
        $booker = $this->userWithRole(60); // Event buchen
        $booker->players()->attach($player->id, ['self' => true]);

        $this->actingAs($this->userWithRole(20))->patchJson(route('adventures.cancel', $adventure))->assertOk();

        // Nach der Absage ist keine Anmeldung mehr möglich.
        $this->actingAs($booker)
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1', 'kontakt_telefon' => '+49 123 456789',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_already_cancelled_event_cannot_be_cancelled_again(): void
    {
        $adventure = Adventure::factory()->create(['event_status_id' => EventStatus::CANCELLED]);

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('adventures.cancel', $adventure))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Das Event ist bereits abgesagt.');
    }

    public function test_completed_event_cannot_be_cancelled(): void
    {
        $adventure = Adventure::factory()->create(['event_status_id' => 60]); // Abgeschlossen (terminal)

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('adventures.cancel', $adventure))
            ->assertStatus(422);

        $this->assertSame(60, $adventure->fresh()->event_status_id);
    }

    public function test_booker_role_cannot_cancel(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(60)) // Event buchen: kein events.edit
            ->patch(route('adventures.cancel', $adventure))
            ->assertForbidden();
    }
}
