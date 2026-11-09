<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-25: Teilnehmerdaten bei Anmeldung korrekt speichern.
 */
class BookingAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    private function booking(Player $player, User $guardian): Booking
    {
        $adventure = Adventure::factory()->create([
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
        ]);

        return Booking::factory()->create([
            'adventure_id' => $adventure->id,
            'player_id' => $player->id,
            'booked_by_user_id' => $guardian->id,
        ]);
    }

    public function test_booking_references_guardian_via_booked_by_user_id(): void
    {
        $guardian = User::factory()->create();
        $player = Player::factory()->create();
        $guardian->players()->attach($player, ['self' => false]);

        $booking = $this->booking($player, $guardian);

        $this->assertEquals($guardian->id, $booking->booked_by_user_id);
        $this->assertEquals($guardian->id, $booking->bookedBy->id);
        $this->assertEquals($guardian->id, $booking->guardian()->id);
    }

    public function test_uses_guardian_address_true_when_same_as_guardian(): void
    {
        $guardian = User::factory()->create();
        $player = Player::factory()->create(['address_same_as_guardian' => true]);
        $guardian->players()->attach($player, ['self' => false]);

        $booking = $this->booking($player, $guardian);
        $booking->load(['player.users', 'bookedBy']);

        $this->assertTrue($booking->usesGuardianAddress());
    }

    public function test_uses_guardian_address_false_when_child_has_own_address(): void
    {
        $guardian = User::factory()->create(['city' => 'Gießen']);
        $player = Player::factory()->create([
            'address_same_as_guardian' => false,
            'city' => 'Wetzlar',
        ]);
        $guardian->players()->attach($player, ['self' => false]);

        $booking = $this->booking($player, $guardian);
        $booking->load(['player.users', 'bookedBy']);

        $this->assertFalse($booking->usesGuardianAddress());
    }

    public function test_effective_city_returns_guardian_city_when_same_as_guardian(): void
    {
        $guardian = User::factory()->create(['city' => 'Gießen']);
        $player = Player::factory()->create(['address_same_as_guardian' => true, 'city' => null]);
        $guardian->players()->attach($player, ['self' => false]);

        $booking = $this->booking($player, $guardian);
        $booking->load(['player.users', 'bookedBy']);

        $this->assertEquals('Gießen', $booking->effective_city);
    }

    public function test_effective_city_returns_child_city_when_different(): void
    {
        $guardian = User::factory()->create(['city' => 'Gießen']);
        $player = Player::factory()->create([
            'address_same_as_guardian' => false,
            'street' => 'Kindstraße',
            'house_number' => '1',
            'zip' => '35578',
            'city' => 'Wetzlar',
        ]);
        $guardian->players()->attach($player, ['self' => false]);

        $booking = $this->booking($player, $guardian);
        $booking->load(['player.users', 'bookedBy']);

        $this->assertEquals('Wetzlar', $booking->effective_city);
    }
}
