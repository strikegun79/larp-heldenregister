<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Hero;
use App\Models\Player;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Startseite: Fortschritts-Chips an der Heldenkarte und Platzhalter für eigene
 * Spieler ohne Helden. Fremde Spieler dürfen nie erscheinen.
 */
class DashboardHeroProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function user(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(10);

        return $user;
    }

    private function playerWithHero(string $heroName): Player
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'character_name' => $heroName]);
        $player->update(['active_hero_id' => $hero->id]);

        return $player;
    }

    public function test_hero_card_shows_skill_and_adventure_counts(): void
    {
        $user = $this->user();
        $player = $this->playerWithHero('Ritter Kunibert');
        $user->players()->attach($player->id, ['self' => true]);

        foreach (Skill::factory()->count(2)->create() as $skill) {
            $player->activeHero->skills()->attach($skill->id, ['trained_at' => now()]);
        }
        Adventure::factory()->create()->visits()->create(['player_id' => $player->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ritter Kunibert')
            ->assertSee('2 Fertigkeiten')
            ->assertSee('1 Abenteuer erlebt');
    }

    public function test_placeholder_for_own_player_without_hero(): void
    {
        $user = $this->user();
        $child = Player::factory()->create(['name' => 'Lena Beispiel']);
        $user->players()->attach($child->id, ['self' => false]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Deine Helden')
            ->assertSee('Held von Lena Beispiel')
            ->assertSee('Wartet noch auf das erste Abenteuer');
    }

    public function test_no_placeholder_when_player_has_heroes_but_none_active(): void
    {
        $user = $this->user();
        $player = Player::factory()->create(['name' => 'Max Muster']);
        Hero::factory()->create(['player_id' => $player->id]);
        $user->players()->attach($player->id, ['self' => false]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Wartet noch auf das erste Abenteuer');
    }

    public function test_foreign_players_and_heroes_are_not_shown(): void
    {
        $user = $this->user();
        $this->playerWithHero('Fremder Held');
        Player::factory()->create(['name' => 'Fremdes Kind']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Fremder Held')
            ->assertDontSee('Fremdes Kind')
            ->assertDontSee('Deine Helden');
    }
}
