<?php

namespace Tests\Feature;

use App\Models\Hero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-03: Tests für die öffentliche Heldensuche per Code. */
class PublicHeroSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_suchseite_ohne_login_erreichbar(): void
    {
        $this->get(route('public.hero.search'))->assertOk();
    }

    public function test_gültiger_code_leitet_auf_profil_weiter(): void
    {
        Hero::factory()->create(['public_code' => 'ABCDEF']);

        $this->get(route('public.hero.search.go', ['code' => 'ABCDEF']))
            ->assertRedirect(route('public.hero', 'ABCDEF'));
    }

    public function test_code_wird_vor_weiterleitung_in_grossbuchstaben_umgewandelt(): void
    {
        Hero::factory()->create(['public_code' => 'ABCDEF']);

        $this->get(route('public.hero.search.go', ['code' => 'abcdef']))
            ->assertRedirect(route('public.hero', 'ABCDEF'));
    }

    public function test_zu_kurze_eingabe_zeigt_fehlermeldung(): void
    {
        $this->get(route('public.hero.search.go', ['code' => 'A']))
            ->assertOk()
            ->assertSee('mindestens 2 Zeichen');
    }

    public function test_leere_eingabe_zeigt_fehlermeldung(): void
    {
        $this->get(route('public.hero.search.go', ['code' => '']))
            ->assertOk()
            ->assertSee('mindestens 2 Zeichen');
    }

    public function test_ungueltige_siegelzeichen_fuehren_zur_namenssuche(): void
    {
        // 0, O, 1, I, L sind im Siegel verboten → keine Weiterleitung, sondern Namenssuche (PUB-08).
        $this->get(route('public.hero.search.go', ['code' => '000000']))
            ->assertOk()
            ->assertViewHas('results', fn ($results) => $results->isEmpty());
    }

    public function test_eingabe_bleibt_bei_fehler_erhalten(): void
    {
        $this->get(route('public.hero.search.go', ['code' => 'XX']))
            ->assertOk()
            ->assertSee('XX');
    }

    public function test_weiterleitung_auch_wenn_held_nicht_existiert(): void
    {
        // Die Suche leitet weiter; /h/{code} gibt dann 404 – das ist korrekt
        $this->get(route('public.hero.search.go', ['code' => 'ZZZZZZ']))
            ->assertRedirect(route('public.hero', 'ZZZZZZ'));
    }
}
