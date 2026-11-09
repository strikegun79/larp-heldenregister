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
 * ADV-13: Bereits angemeldete Spieler erscheinen nicht mehr im Buchungs-Dropdown.
 */
class BookingPlayerExclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function bookerWith(array $players): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(60); // Event buchen
        foreach ($players as $p) {
            $user->players()->attach($p->id, ['self' => true]);
        }

        return $user;
    }

    public function test_already_booked_player_is_excluded_from_form(): void
    {
        $booked = Player::factory()->create(['name' => 'SchonDa']);
        $free = Player::factory()->create(['name' => 'NochFrei']);
        $booker = $this->bookerWith([$booked, $free]);

        $adventure = Adventure::factory()->create(['max_player' => 5]);
        Booking::factory()->for($adventure)->create(['player_id' => $booked->id]);

        $this->actingAs($booker)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertSee('NochFrei')
            ->assertDontSee('SchonDa');
    }

    public function test_registrar_dropdown_excludes_booked_players(): void
    {
        $booked = Player::factory()->create(['name' => 'SchonDa']);
        $free = Player::factory()->create(['name' => 'NochFrei']);

        $registrar = User::factory()->create();
        $registrar->roles()->attach(20); // Bürokrat: book-any-player

        $adventure = Adventure::factory()->create(['max_player' => 5]);
        Booking::factory()->for($adventure)->create(['player_id' => $booked->id]);

        $this->actingAs($registrar)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertSee('NochFrei')
            ->assertDontSee('SchonDa');
    }

    public function test_hint_when_all_players_already_booked(): void
    {
        $player = Player::factory()->create();
        $booker = $this->bookerWith([$player]);

        $adventure = Adventure::factory()->create(['max_player' => 5]);
        Booking::factory()->for($adventure)->create(['player_id' => $player->id]);

        $this->actingAs($booker)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertSee('bereits angemeldet');
    }
}
