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

class AttendanceTest extends TestCase
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

    private function adventureWithBookings(int $count = 2): array
    {
        $adventure = Adventure::factory()->create();
        $players = Player::factory()->count($count)->create();
        foreach ($players as $player) {
            Booking::factory()->for($adventure)->create(['player_id' => $player->id]);
        }

        return [$adventure, $players];
    }

    public function test_spielleiter_records_and_clears_attendance(): void
    {
        [$adventure, $players] = $this->adventureWithBookings(2);
        $gm = $this->userWithRole(40); // Spielleiter

        $this->actingAs($gm)
            ->putJson(route('adventures.attendance', $adventure), ['present' => [$players[0]->id]])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertDatabaseHas('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $players[0]->id]);
        $this->assertDatabaseMissing('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $players[1]->id]);

        // Abhaken entfernen -> Visit weg.
        $this->actingAs($gm)
            ->putJson(route('adventures.attendance', $adventure), ['present' => []])
            ->assertOk();
        $this->assertDatabaseMissing('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $players[0]->id]);
    }

    public function test_only_booked_players_can_be_marked_present(): void
    {
        [$adventure, $players] = $this->adventureWithBookings(1);
        $stranger = Player::factory()->create(); // nicht gebucht

        $this->actingAs($this->userWithRole(40))
            ->putJson(route('adventures.attendance', $adventure), [
                'present' => [$players[0]->id, $stranger->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $players[0]->id]);
        $this->assertDatabaseMissing('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $stranger->id]);
    }

    public function test_admin_can_record_attendance(): void
    {
        [$adventure, $players] = $this->adventureWithBookings(1);

        $this->actingAs($this->userWithRole(10))
            ->putJson(route('adventures.attendance', $adventure), ['present' => [$players[0]->id]])
            ->assertOk();

        $this->assertDatabaseHas('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $players[0]->id]);
    }

    public function test_event_booking_role_cannot_record_attendance(): void
    {
        [$adventure, $players] = $this->adventureWithBookings(1);

        $this->actingAs($this->userWithRole(60)) // Event buchen darf NICHT
            ->put(route('adventures.attendance', $adventure), ['present' => [$players[0]->id]])
            ->assertForbidden();

        $this->assertDatabaseCount('event_visits', 0);
    }
}
