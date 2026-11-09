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

class BookingWaitlistPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat hat adventure.cancel

        return $user;
    }

    private function waitlisted(Adventure $adventure): Booking
    {
        return Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'waitlisted' => true,
        ]);
    }

    public function test_oldest_waitlisted_booking_is_promoted_when_a_regular_spot_is_freed(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 1]);
        $regular = Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'waitlisted' => false,
        ]);
        $first = $this->waitlisted($adventure);  // ältester Wartelisten-Eintrag
        $second = $this->waitlisted($adventure); // jüngerer Eintrag

        $this->actingAs($this->registrar())
            ->deleteJson(route('adventures.bookings.destroy', [$adventure, $regular]))
            ->assertOk();

        $this->assertDatabaseMissing('bookings', ['id' => $regular->id]);
        $this->assertFalse($first->fresh()->waitlisted);  // nachgerückt
        $this->assertTrue($second->fresh()->waitlisted);   // bleibt auf der Warteliste
    }

    public function test_cancelling_a_waitlisted_booking_does_not_promote_anyone(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 1]);
        Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'waitlisted' => false,
        ]);
        $waitA = $this->waitlisted($adventure);
        $waitB = $this->waitlisted($adventure);

        $this->actingAs($this->registrar())
            ->deleteJson(route('adventures.bookings.destroy', [$adventure, $waitA]))
            ->assertOk();

        // Der verbleibende Wartelisten-Eintrag rückt NICHT nach.
        $this->assertTrue($waitB->fresh()->waitlisted);
    }

    public function test_no_promotion_when_nobody_is_waiting(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 1]);
        $regular = Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'waitlisted' => false,
        ]);

        $this->actingAs($this->registrar())
            ->deleteJson(route('adventures.bookings.destroy', [$adventure, $regular]))
            ->assertOk()
            ->assertJsonMissing(['message' => null]);

        $this->assertDatabaseMissing('bookings', ['id' => $regular->id]);
        $this->assertDatabaseCount('bookings', 0);
    }
}
