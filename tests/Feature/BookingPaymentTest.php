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

class BookingPaymentTest extends TestCase
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
            'paid' => false,
        ]);
    }

    public function test_registrar_toggles_paid_status(): void
    {
        $booking = $this->booking();
        $route = route('adventures.bookings.payment', [$booking->adventure_id, $booking]);

        $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->patchJson($route)
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);
        $this->assertTrue($booking->fresh()->paid);

        // Erneuter Aufruf setzt wieder auf offen.
        $this->actingAs($this->userWithRole(20))->patchJson($route)->assertOk();
        $this->assertFalse($booking->fresh()->paid);
    }

    public function test_admin_can_toggle_paid(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->userWithRole(10))
            ->patchJson(route('adventures.bookings.payment', [$booking->adventure_id, $booking]))
            ->assertOk();

        $this->assertTrue($booking->fresh()->paid);
    }

    public function test_game_master_cannot_toggle_paid(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->userWithRole(40))
            ->patchJson(route('adventures.bookings.payment', [$booking->adventure_id, $booking]))
            ->assertForbidden();

        $this->assertFalse($booking->fresh()->paid);
    }

    public function test_booking_must_belong_to_adventure(): void
    {
        $booking = $this->booking();
        $otherAdventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('adventures.bookings.payment', [$otherAdventure, $booking]))
            ->assertNotFound();
    }
}
