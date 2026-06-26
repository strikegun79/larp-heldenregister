<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-02: Tests für das öffentliche Helden-Profil /h/{code}. */
class PublicHeroProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_ohne_login_erreichbar(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'TESTAA']);

        $this->get(route('public.hero', 'TESTAA'))->assertOk();
    }

    public function test_charaktername_wird_angezeigt(): void
    {
        $hero = Hero::factory()->create([
            'character_name' => 'Aldric der Kühne',
            'public_code'    => 'ALDRIC',
        ]);

        $this->get(route('public.hero', 'ALDRIC'))
             ->assertOk()
             ->assertSee('Aldric der Kühne');
    }

    public function test_spieler_realname_wird_nicht_angezeigt(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Mustermann']);
        $hero   = Hero::factory()->create([
            'player_id'   => $player->id,
            'public_code' => 'NONAME',
        ]);

        $this->get(route('public.hero', 'NONAME'))
             ->assertOk()
             ->assertDontSee('Max')
             ->assertDontSee('Mustermann');
    }

    public function test_unbekannter_code_liefert_404(): void
    {
        $this->get(route('public.hero', 'XXXXXX'))->assertNotFound();
    }

    public function test_code_wird_case_insensitive_aufgeloest(): void
    {
        Hero::factory()->create(['public_code' => 'ABCDEF']);

        $this->get(route('public.hero', 'abcdef'))->assertOk();
    }

    public function test_heimatort_wird_angezeigt(): void
    {
        $hero = Hero::factory()->create([
            'homeplace'   => 'Waldheim',
            'public_code' => 'WALDHM',
        ]);

        $this->get(route('public.hero', 'WALDHM'))
             ->assertOk()
             ->assertSee('Waldheim');
    }

    public function test_public_code_wird_auf_seite_angezeigt(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'SHOWME']);

        $this->get(route('public.hero', 'SHOWME'))
             ->assertOk()
             ->assertSee('SHOWME');
    }

    public function test_kein_geburtsdatum_sichtbar(): void
    {
        $hero = Hero::factory()->create([
            'born'        => '2010-05-15',
            'public_code' => 'NOBORN',
        ]);

        $this->get(route('public.hero', 'NOBORN'))
             ->assertOk()
             ->assertDontSee('15.05.2010')
             ->assertDontSee('2010-05-15');
    }
}
