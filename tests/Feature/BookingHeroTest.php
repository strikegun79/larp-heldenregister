<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HERO-21: Bei der Event-Anmeldung wird automatisch der aktive Held des
 * Spielers mitgespeichert – ohne Auswahlmöglichkeit.
 */
class BookingHeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function bookerWithPlayer(Player $player): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(60); // Event buchen
        $user->players()->attach($player->id, ['self' => true]);

        return $user;
    }

    public function test_create_form_has_no_hero_selection(): void
    {
        $player = Player::factory()->create();
        Hero::factory()->create(['player_id' => $player->id]);
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWithPlayer($player))
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertDontSee('name="hero_id"', false);
    }

    public function test_booking_auto_assigns_active_hero(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);
        $player->update(['active_hero_id' => $hero->id]);

        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWithPlayer($player))
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])
            ->assertOk();

        $this->assertDatabaseHas('bookings', [
            'adventure_id' => $adventure->id,
            'player_id' => $player->id,
            'hero_id' => $hero->id,
        ]);
    }

    public function test_booking_without_active_hero_stores_null(): void
    {
        $player = Player::factory()->create(); // kein aktiver Held
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWithPlayer($player))
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])
            ->assertOk();

        $this->assertDatabaseHas('bookings', [
            'adventure_id' => $adventure->id,
            'player_id' => $player->id,
            'hero_id' => null,
        ]);
    }
}
