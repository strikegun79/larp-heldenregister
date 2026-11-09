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
 * ADV-18: Anmelde-Status (offen/bestätigt/abgelehnt/abgemeldet), Anzeige im
 * Anmeldungen-Tab sowie Check-in/Abmelden je Teilnehmer.
 */
class BookingStatusTest extends TestCase
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

    private function bookingFor(Adventure $adventure, array $attrs = []): Booking
    {
        return Booking::factory()->for($adventure)->create(array_merge([
            'player_id' => Player::factory()->create()->id,
        ], $attrs));
    }

    public function test_booking_tab_shows_status_and_payment_for_a_player(): void
    {
        $user = $this->userWithRole(60); // Event buchen
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);

        $adventure = Adventure::factory()->create();
        $this->bookingFor($adventure, ['player_id' => $player->id, 'status' => 'bestaetigt', 'paid' => true]);

        $this->actingAs($user)
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('bestätigt')
            ->assertSee('bezahlt');
    }

    public function test_registrar_can_reject_and_unreject(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);
        $route = route('adventures.bookings.rejection', [$adventure, $booking]);

        $this->actingAs($this->userWithRole(20))->patchJson($route)->assertOk();
        $this->assertSame('abgelehnt', $booking->fresh()->status);

        // Erneut -> zurück auf offen.
        $this->actingAs($this->userWithRole(20))->patchJson($route)->assertOk();
        $this->assertSame('offen', $booking->fresh()->status);
    }

    public function test_confirm_sets_status_bestaetigt(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('adventures.bookings.approval', [$adventure, $booking]))
            ->assertOk();

        $this->assertSame('bestaetigt', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->approved_at);
    }

    public function test_deregister_sets_status_with_reason_and_removes_checkin(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);
        $adventure->visits()->create(['player_id' => $booking->player_id]); // war eingecheckt

        $this->actingAs($this->userWithRole(30)) // Projektleitung: manage-checkin
            ->patchJson(route('adventures.bookings.deregister', [$adventure, $booking]), [
                'absence_reason' => 'krank',
            ])
            ->assertOk();

        $booking->refresh();
        $this->assertSame('abgemeldet', $booking->status);
        $this->assertSame('krank', $booking->absence_reason);
        $this->assertDatabaseMissing('event_visits', [
            'adventure_id' => $adventure->id,
            'player_id' => $booking->player_id,
        ]);
    }

    public function test_deregister_requires_valid_reason(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(30))
            ->patchJson(route('adventures.bookings.deregister', [$adventure, $booking]), [
                'absence_reason' => 'unsinn',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('absence_reason');
    }

    public function test_single_checkin_toggle(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $booking = $this->bookingFor($adventure);
        $route = route('adventures.bookings.checkin', [$adventure, $booking]);

        $this->actingAs($this->userWithRole(30))->patchJson($route)->assertOk();
        $this->assertDatabaseHas('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $booking->player_id]);

        // Erneut -> ausgecheckt.
        $this->actingAs($this->userWithRole(30))->patchJson($route)->assertOk();
        $this->assertDatabaseMissing('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $booking->player_id]);
    }

    public function test_reject_requires_approval_permission(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        // Spielleiter hat adventure.modify, aber nicht approve-bookings.
        $this->actingAs($this->userWithRole(40))
            ->patchJson(route('adventures.bookings.rejection', [$adventure, $booking]))
            ->assertForbidden();
    }
}
