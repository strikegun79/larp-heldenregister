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
 * Anmeldungen-Tab: Aktions-Icons mit Tooltips; „Event buchen" kann eigene
 * Anmeldung stornieren.
 */
class BookingActionIconsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function detail(User $user, Adventure $adventure)
    {
        return $this->actingAs($user)
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_event_booking_user_sees_storno_for_own_booking(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(60); // Event buchen (hat adventure.cancel)
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);

        $adventure = Adventure::factory()->create();
        $booking = Booking::factory()->for($adventure)->create(['player_id' => $player->id]);

        $response = $this->detail($user, $adventure)->assertOk();

        $response->assertSee(route('adventures.bookings.destroy', [$adventure, $booking]), false);
        $response->assertSee('data-tooltip="Stornieren"', false);
        $response->assertSee('times icon', false);
    }

    public function test_event_booking_user_can_actually_cancel(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(60);
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);

        $adventure = Adventure::factory()->create();
        $booking = Booking::factory()->for($adventure)->create(['player_id' => $player->id]);

        $this->actingAs($user)
            ->deleteJson(route('adventures.bookings.destroy', [$adventure, $booking]))
            ->assertOk();

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_management_actions_use_icon_tooltips(): void
    {
        $registrar = User::factory()->create();
        $registrar->roles()->attach(20); // Bürokrat: approve + pay + modify + cancel
        $adventure = Adventure::factory()->create();
        Booking::factory()->for($adventure)->create(['player_id' => Player::factory()->create()->id]);

        $response = $this->detail($registrar, $adventure)->assertOk();

        $response->assertSee('data-tooltip="Bestätigen"', false);
        $response->assertSee('data-tooltip="Ablehnen"', false);
        $response->assertSee('data-tooltip="Als bezahlt markieren"', false);
        $response->assertSee('data-tooltip="Bearbeiten"', false);
        $response->assertSee('coins icon', false);
    }
}
