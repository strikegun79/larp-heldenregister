<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spielerliste im Waldritter-Design: Seitenkopf, Suche mit Label, Karte mit aktivem Helden,
 * korrigierter Leerzustand, nur eigene Spieler.
 */
class PlayerListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function parent(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);

        return $user;
    }

    public function test_card_shows_active_hero_with_class_and_self_marker(): void
    {
        $user = $this->parent();
        $player = Player::factory()->create(['name' => 'Lena', 'lastname' => 'Beispiel']);
        $user->players()->attach($player->id, ['self' => true]);
        $hero = Hero::factory()->for($player)->create(['character_name' => 'Waldläuferin Ilva']);
        $class = HeroClass::create(['id' => 980, 'name' => 'Waldläufer', 'slug' => 'test-980', 'ep_cost' => 0]);
        $hero->classes()->attach($class->id);
        $player->update(['active_hero_id' => $hero->id]);

        $this->actingAs($user)->get(route('players.index'))
            ->assertOk()
            ->assertSee('<h1 class="wr-heading">Deine Spieler</h1>', false)
            ->assertSee('Lena Beispiel')
            ->assertSee('Waldläuferin Ilva')
            ->assertSee('wr-class-badge', false)
            ->assertSee('>Du<', false);
    }

    public function test_player_without_hero_explains_hero_creation(): void
    {
        $user = $this->parent();
        $user->players()->attach(Player::factory()->create()->id, ['self' => false]);

        $this->actingAs($user)->get(route('players.index'))
            ->assertOk()
            ->assertSee('noch kein Held – er entsteht beim ersten Abenteuer');
    }

    public function test_empty_state_has_corrected_buerokrat_text_and_search_label(): void
    {
        $this->actingAs($this->parent())->get(route('players.index'))
            ->assertOk()
            ->assertSee('<label for="players-search"', false)
            ->assertSee('die die Helden ins Heldenregister einträgt')
            ->assertDontSee('Spielcharakter');
    }

    public function test_only_own_players_are_listed(): void
    {
        $user = $this->parent();
        $user->players()->attach(Player::factory()->create(['name' => 'Eigenes', 'lastname' => 'Kind'])->id, ['self' => false]);
        Player::factory()->create(['name' => 'Fremdes', 'lastname' => 'Kind']);

        $this->actingAs($user)->get(route('players.index'))
            ->assertOk()
            ->assertSee('Eigenes Kind')
            ->assertDontSee('Fremdes Kind');
    }
}
