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
 * PLAY-07: Geburtsdatum-Validierung, Alter-Accessor und Alter in Listen/PDF.
 */
class PlayerAgeTest extends TestCase
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

    public function test_age_accessor(): void
    {
        $player = Player::factory()->create(['dayofbirth' => now()->subYears(14)->toDateString()]);
        $this->assertSame(14, $player->age);

        $this->assertNull(Player::factory()->create(['dayofbirth' => null])->age);
    }

    public function test_future_birthdate_is_rejected(): void
    {
        $user = $this->userWithRole(70);

        $this->actingAs($user)
            ->post(route('players.store'), [
                'name' => 'Zeit', 'lastname' => 'Reisender',
                'dayofbirth' => now()->addYear()->toDateString(),
            ])
            ->assertSessionHasErrors('dayofbirth');
    }

    public function test_plausible_birthdate_is_accepted(): void
    {
        $user = $this->userWithRole(70);

        $this->actingAs($user)
            ->post(route('players.store'), [
                'name' => 'Klein', 'lastname' => 'Held', 'dayofbirth' => now()->subYears(10)->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(10, Player::firstWhere('name', 'Klein')->age);
    }

    public function test_age_in_admin_players_list(): void
    {
        Player::factory()->create(['name' => 'Mira', 'dayofbirth' => now()->subYears(9)->toDateString()]);

        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.players.index'))
            ->assertOk()
            ->assertSee('9 J.');
    }

    public function test_age_in_booking_overview(): void
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create(['dayofbirth' => now()->subYears(13)->toDateString()]);
        Booking::factory()->for($adventure)->create(['player_id' => $player->id]);

        $this->actingAs($this->userWithRole(20)) // Bürokrat: view-all-bookings
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Alter')
            ->assertSee('>13<', false);
    }

    public function test_guest_age_is_used_for_guest_bookings(): void
    {
        $adventure = Adventure::factory()->create();
        $guest = Booking::factory()->for($adventure)->create([
            'player_id' => null, 'guest_name' => 'G', 'guest_lastname' => 'Ast', 'guest_age' => 8,
        ]);

        $this->assertSame(8, $guest->participant_age);
    }
}
