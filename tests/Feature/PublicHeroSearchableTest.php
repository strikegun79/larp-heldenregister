<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-07 + PUB-08: Betreuer-Zugriff und öffentliche Namenssuche. */
class PublicHeroSearchableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // --- PUB-07: Betreuer-Zugriffsrechte ---

    public function test_betreuer_kann_sichtbarkeit_umschalten(): void
    {
        $player  = Player::factory()->create();
        $betreuer = User::factory()->create();
        $betreuer->players()->attach($player);
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_visible' => true]);

        $this->actingAs($betreuer)
             ->patch(route('heroes.visibility', $hero))
             ->assertRedirect();

        $this->assertFalse($hero->fresh()->public_visible);
    }

    public function test_fremder_nutzer_kann_sichtbarkeit_nicht_umschalten(): void
    {
        $hero  = Hero::factory()->create(['public_visible' => true]);
        $other = User::factory()->create();
        // Kein heldenregister.edit, kein Betreuer dieses Spielers.
        $other->roles()->attach(70); // Teilnehmer

        $this->actingAs($other)
             ->patch(route('heroes.visibility', $hero))
             ->assertForbidden();

        $this->assertTrue($hero->fresh()->public_visible);
    }

    public function test_admin_sieht_url_auch_bei_verstecktem_held(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10); // Admin
        $hero  = Hero::factory()->create(['public_visible' => false, 'public_code' => 'ADMURL']);

        $this->actingAs($admin)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('ADMURL')
             ->assertSee('aktuell versteckt');
    }

    // --- PUB-08: Opt-in Heldensuche ---

    public function test_held_mit_public_searchable_true_erscheint_in_namenssuche(): void
    {
        Hero::factory()->create([
            'character_name'    => 'Aldric der Tapfere',
            'public_visible'    => true,
            'public_searchable' => true,
            'public_code'       => 'SRCH01',
        ]);

        $this->get(route('public.hero.search.go', ['code' => 'Aldric']))
             ->assertOk()
             ->assertSee('Aldric der Tapfere');
    }

    public function test_nicht_suchbarer_held_erscheint_nicht_in_namenssuche(): void
    {
        Hero::factory()->create([
            'character_name'    => 'Verborgener Ritter',
            'public_visible'    => true,
            'public_searchable' => false,
            'public_code'       => 'SRCH02',
        ]);

        $this->get(route('public.hero.search.go', ['code' => 'Verborgener']))
             ->assertOk()
             ->assertDontSee('Verborgener Ritter');
    }

    public function test_versteckter_held_erscheint_nicht_in_namenssuche(): void
    {
        Hero::factory()->create([
            'character_name'    => 'Unsichtbarer Held',
            'public_visible'    => false,
            'public_searchable' => true,
            'public_code'       => 'SRCH03',
        ]);

        $this->get(route('public.hero.search.go', ['code' => 'Unsichtbarer']))
             ->assertOk()
             ->assertDontSee('Unsichtbarer Held');
    }

    public function test_code_suche_leitet_weiterhin_direkt_weiter(): void
    {
        // Code nur mit gültigen Base31-Zeichen (kein 0, I, L, O)
        Hero::factory()->create(['public_code' => 'SRCH45', 'public_visible' => true]);

        $this->get(route('public.hero.search.go', ['code' => 'SRCH45']))
             ->assertRedirect(route('public.hero', 'SRCH45'));
    }

    public function test_zu_kurze_eingabe_zeigt_fehlermeldung(): void
    {
        $this->get(route('public.hero.search.go', ['code' => 'X']))
             ->assertOk()
             ->assertSee('mindestens 2 Zeichen');
    }

    public function test_betreuer_kann_suche_umschalten(): void
    {
        $player   = Player::factory()->create();
        $betreuer = User::factory()->create();
        $betreuer->players()->attach($player);
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_searchable' => true]);

        $this->actingAs($betreuer)
             ->patch(route('heroes.searchable', $hero))
             ->assertRedirect();

        $this->assertFalse($hero->fresh()->public_searchable);
    }

    public function test_neuer_held_ist_standardmässig_suchbar(): void
    {
        $hero = Hero::factory()->create();
        $this->assertTrue($hero->public_searchable);
    }
}
