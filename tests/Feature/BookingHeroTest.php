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
 * ADV-14: Bei der Event-Anmeldung wird der aktive Held des Spielers vorgewählt
 * und mitgespeichert; ohne aktiven Helden ein Hinweis.
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

    public function test_create_form_preselects_active_hero(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'character_name' => 'Thorgal']);
        $player->update(['active_hero_id' => $hero->id]);

        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWithPlayer($player))
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertSee('data-hero-id="'.$hero->id.'"', false)
            ->assertSee('Thorgal');
    }

    public function test_create_form_shows_hint_when_no_active_hero(): void
    {
        $player = Player::factory()->create();
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWithPlayer($player))
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertSee('wende dich im nächsten Spiel an den Bürokraten', false);
    }

    public function test_booking_stores_the_chosen_hero(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);
        $player->update(['active_hero_id' => $hero->id]);

        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWithPlayer($player))
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'hero_id' => $hero->id,
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

    public function test_booking_rejects_hero_of_other_player(): void
    {
        $player = Player::factory()->create();
        $otherHero = Hero::factory()->create(['player_id' => Player::factory()->create()->id]);

        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWithPlayer($player))
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'hero_id' => $otherHero->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Der gewählte Held gehört nicht zum Spieler.');

        $this->assertDatabaseCount('bookings', 0);
    }
}
