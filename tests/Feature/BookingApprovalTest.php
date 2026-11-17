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

class BookingApprovalTest extends TestCase
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

    private function booking(): Booking
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create();

        return Booking::factory()->for($adventure)->create([
            'player_id' => $player->id,
            'status' => 'offen',
            'approved_at' => null,
        ]);
    }

    public function test_registrar_confirms_open_booking(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->patchJson(route('adventures.bookings.confirm', [$booking->adventure_id, $booking]))
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $booking->refresh();
        $this->assertSame('bestaetigt', $booking->status);
        $this->assertNotNull($booking->approved_at);
    }

    public function test_already_confirmed_booking_cannot_be_confirmed_again(): void
    {
        $booking = $this->booking();
        $booking->update(['status' => 'bestaetigt', 'approved_at' => now()->subDay()]);

        // Kein Toggle mehr: Rücknahme läuft über die Ablehnung.
        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('adventures.bookings.confirm', [$booking->adventure_id, $booking]))
            ->assertStatus(422);
    }

    public function test_admin_can_confirm_booking(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->userWithRole(10))
            ->patchJson(route('adventures.bookings.confirm', [$booking->adventure_id, $booking]))
            ->assertOk();

        $this->assertNotNull($booking->fresh()->approved_at);
    }

    public function test_game_master_cannot_confirm_booking(): void
    {
        $booking = $this->booking();

        // Spielleiter hat adventure.modify, aber nicht approve-bookings.
        $this->actingAs($this->userWithRole(40))
            ->patchJson(route('adventures.bookings.confirm', [$booking->adventure_id, $booking]))
            ->assertForbidden();

        $this->assertNull($booking->fresh()->approved_at);
    }

    public function test_booking_must_belong_to_adventure(): void
    {
        $booking = $this->booking();
        $otherAdventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('adventures.bookings.confirm', [$otherAdventure, $booking]))
            ->assertNotFound();
    }
}
