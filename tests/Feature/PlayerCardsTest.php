<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PLAY-10: Spielerliste als Karten mit Avatar, „Neuer Spieler"-Karte und Modal.
 */
class PlayerCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithPlayer(Player $player): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70); // Teilnehmer (Profil/Spieler)
        $user->players()->attach($player->id, ['self' => false]);

        return $user;
    }

    public function test_index_shows_new_player_card_and_player_card(): void
    {
        $player = Player::factory()->create(['name' => 'Mira', 'lastname' => 'Tan', 'gender' => 'weiblich']);
        $user = $this->userWithPlayer($player);

        $response = $this->actingAs($user)->get(route('players.index'))->assertOk();

        // Neuer-Spieler-Karte
        $response->assertSee('Neuen Spieler erstellen');
        $response->assertSee('/images/wewantyou_poster4.jpg', false);
        $response->assertSee('data-modal-url="'.route('players.create').'"', false);

        // Papyrus-Hintergrund gehört auf die Spielerkarten (Korrektur PLAY-11).
        $response->assertSee('/images/player_background.png', false);

        // Spielerkarte mit Feldern
        $response->assertSee('Mira Tan');
        $response->assertSee('Erstellt:');
        $response->assertSee('Geschlecht:');
        $response->assertSee('Besuchte Events:');
        $response->assertSee('data-modal-url="'.route('players.show', $player).'"', false);
    }

    public function test_default_avatar_when_no_image(): void
    {
        $player = Player::factory()->create(['image' => null]);

        $this->assertSame('/images/player_default_avatar.jpg', $player->avatar_url);
    }

    public function test_create_returns_modal_partial_on_ajax(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);

        $this->actingAs($user)
            ->get(route('players.create'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Neuer Spieler')
            ->assertSee('data-modal-title', false);
    }

    public function test_can_create_player_without_avatar(): void
    {
        // Avatar-Upload beim Anlegen entfernt (PLAY-12); Upload erfolgt über den Avatar-Tab.
        $user = User::factory()->create();
        $user->roles()->attach(70);

        $this->actingAs($user)
            ->post(route('players.store'), [
                'name' => 'Lea', 'lastname' => 'Berg',
            ])
            ->assertRedirect();

        $player = Player::firstWhere('name', 'Lea');
        $this->assertNotNull($player);
        $this->assertNull($player->image);
        $this->assertSame('/images/player_default_avatar.jpg', $player->avatar_url);
    }

    public function test_visits_count_reflects_attended_events(): void
    {
        $player = Player::factory()->create();
        $user = $this->userWithPlayer($player);
        $adventure = Adventure::factory()->create();
        $adventure->visits()->create(['player_id' => $player->id]);

        $this->actingAs($user)
            ->get(route('players.index'))
            ->assertOk()
            ->assertSee('Besuchte Events:</span> 1', false);
    }
}
