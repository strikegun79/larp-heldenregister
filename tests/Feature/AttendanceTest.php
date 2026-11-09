<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EpTransaction;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
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
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    private function adventureWithBookings(int $count = 2): array
    {
        // Check-in erst ab Status „Anmeldung geschlossen" (ADV-14).
        $adventure = Adventure::factory()->registrationClosed()->create();
        $players = Player::factory()->count($count)->create();
        foreach ($players as $player) {
            Booking::factory()->for($adventure)->create(['player_id' => $player->id]);
        }

        return [$adventure, $players];
    }

    public function test_spielleiter_records_and_clears_attendance(): void
    {
        [$adventure, $players] = $this->adventureWithBookings(2);
        $gm = $this->userWithRole(30); // Projektleitung (manage-checkin)

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

        $this->actingAs($this->userWithRole(30))
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

    public function test_awards_ep_to_active_heroes_and_is_idempotent(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create([
            'loot_ep_day' => 3,
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-02 16:00', // 2 Tage
        ]);
        $player = Player::factory()->create();
        Booking::factory()->for($adventure)->create(['player_id' => $player->id]);
        $hero = Hero::factory()->create(['player_id' => $player->id]);
        $player->update(['active_hero_id' => $hero->id]);
        $adventure->visits()->create(['player_id' => $player->id]);

        $this->actingAs($this->userWithRole(30))
            ->postJson(route('adventures.award-ep', $adventure))
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        // 3 EP/Tag × 2 Tage = 6, Typ 50, mit adventure_id
        $this->assertDatabaseHas('ep_transactions', [
            'hero_id' => $hero->id,
            'adventure_id' => $adventure->id,
            'ep_transaction_type_id' => 50,
            'ep_count' => 6,
        ]);

        // Idempotent: zweiter Lauf vergibt nicht erneut.
        $this->actingAs($this->userWithRole(30))->postJson(route('adventures.award-ep', $adventure))->assertOk();
        $this->assertEquals(1, EpTransaction::where('hero_id', $hero->id)->where('adventure_id', $adventure->id)->count());
    }

    public function test_attendee_without_active_hero_is_skipped(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create([
            'loot_ep_day' => 2,
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-01 18:00',
        ]);
        $player = Player::factory()->create(); // kein aktiver Held
        Booking::factory()->for($adventure)->create(['player_id' => $player->id]);
        $adventure->visits()->create(['player_id' => $player->id]);

        $this->actingAs($this->userWithRole(30))
            ->postJson(route('adventures.award-ep', $adventure))
            ->assertOk();

        $this->assertDatabaseCount('ep_transactions', 0);
    }

    public function test_event_booking_role_cannot_award_ep(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(60))
            ->post(route('adventures.award-ep', $adventure))
            ->assertForbidden();
    }
}
