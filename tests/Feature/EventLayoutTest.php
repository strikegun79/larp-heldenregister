<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-15: Event-Detail-Modal – Funktionsmail, rollenabhängige Anmeldungs-
 * sichtbarkeit und das Anmelden als Modal-Unteransicht.
 */
class EventLayoutTest extends TestCase
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

    private function detail(User $user, Adventure $adventure)
    {
        return $this->actingAs($user)
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_detail_shows_function_email_as_mailto(): void
    {
        $adventure = Adventure::factory()->create(['function_email' => 'orga@waldritter.test']);

        $this->detail($this->userWithRole(40), $adventure)
            ->assertOk()
            ->assertSee('mailto:orga@waldritter.test', false)
            ->assertSee('orga@waldritter.test');
    }

    public function test_game_master_sees_all_bookings(): void
    {
        $adventure = Adventure::factory()->create();
        $own = Player::factory()->create(['name' => 'Eigenspieler']);
        $foreign = Player::factory()->create(['name' => 'Fremdspieler']);
        Booking::factory()->for($adventure)->create(['player_id' => $own->id]);
        Booking::factory()->for($adventure)->create(['player_id' => $foreign->id]);

        $this->detail($this->userWithRole(40), $adventure) // Spielleiter: alle sichtbar
            ->assertOk()
            ->assertSee('Eigenspieler')
            ->assertSee('Fremdspieler');
    }

    public function test_event_booking_role_sees_only_own_players_bookings(): void
    {
        $user = $this->userWithRole(60); // Event buchen: nur eigene
        $own = Player::factory()->create(['name' => 'Eigenspieler']);
        $foreign = Player::factory()->create(['name' => 'Fremdspieler']);
        $user->players()->attach($own->id, ['self' => true]);

        $adventure = Adventure::factory()->create();
        Booking::factory()->for($adventure)->create(['player_id' => $own->id]);
        Booking::factory()->for($adventure)->create(['player_id' => $foreign->id]);

        $this->detail($user, $adventure)
            ->assertOk()
            ->assertSee('Eigenspieler')
            ->assertDontSee('Fremdspieler');
    }

    public function test_anmelden_button_opens_booking_subview(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        // Das Detail bietet den Anmelden-Button als gestapeltes Modal an (ADV-22).
        $this->detail($this->userWithRole(60), $adventure)
            ->assertOk()
            ->assertSee('data-modal-stack', false)
            ->assertSee(route('adventures.bookings.create', $adventure), false);

        // Die Unteransicht selbst liefert das Anmeldeformular (Bucher mit eigenem Spieler).
        $booker = $this->userWithRole(60);
        $booker->players()->attach(\App\Models\Player::factory()->create()->id, ['self' => true]);
        $this->actingAs($booker)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertSee('Anmeldung absenden');
    }

    public function test_participant_cannot_open_booking_form(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(70)) // Teilnehmer: kein adventure.book
            ->get(route('adventures.bookings.create', $adventure))
            ->assertForbidden();
    }

    public function test_function_email_must_be_valid(): void
    {
        $adventure = Adventure::factory()->create();

        $payload = [
            'name' => $adventure->name,
            'function_email' => 'keine-email',
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-02 16:00',
            'event_status_id' => $adventure->event_status_id,
            'event_client_id' => $adventure->event_client_id,
            'event_category_id' => $adventure->event_category_id,
            'max_player' => 10,
            'fee' => 12,
        ];

        $this->actingAs($this->userWithRole(20)) // Bürokrat: events.edit
            ->put(route('adventures.update', $adventure), $payload)
            ->assertSessionHasErrors('function_email');
    }
}
