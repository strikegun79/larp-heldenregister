<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-14: Check-in nur ab Status „Anmeldung geschlossen" (≥ 40) und nur durch
 * Admin/Projektleitung/Bürokrat; korrekte Status-Nummerierung.
 */
class EventCheckinRulesTest extends TestCase
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

    private function bookingFor(Adventure $adventure): Booking
    {
        return Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
        ]);
    }

    public function test_checkin_is_blocked_before_status_40(): void
    {
        $adventure = Adventure::factory()->create(); // Status 30 (Anmeldung offen)
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(30)) // Projektleitung
            ->patchJson(route('adventures.bookings.checkin', [$adventure, $booking]))
            ->assertStatus(422);

        $this->assertDatabaseCount('event_visits', 0);
    }

    public function test_award_ep_is_blocked_before_status_40(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(30))
            ->postJson(route('adventures.award-ep', $adventure))
            ->assertStatus(422);
    }

    public function test_checkin_allowed_from_status_40(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->patchJson(route('adventures.bookings.checkin', [$adventure, $booking]))
            ->assertOk();

        $this->assertDatabaseHas('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $booking->player_id]);
    }

    public function test_game_master_cannot_checkin(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $booking = $this->bookingFor($adventure);

        // Spielleiter ist nicht (mehr) berechtigt – nur Admin/Projektleitung/Bürokrat.
        $this->actingAs($this->userWithRole(40))
            ->patchJson(route('adventures.bookings.checkin', [$adventure, $booking]))
            ->assertForbidden();
    }

    public function test_status_numbering_matches_spec(): void
    {
        $this->assertSame('Abrechnung', EventStatus::find(50)->description);
        $this->assertSame('Abgeschlossen', EventStatus::find(60)->description);
        $this->assertSame('abgesagt', EventStatus::find(70)->description);
    }
}
