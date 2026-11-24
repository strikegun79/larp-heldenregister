<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\PlotCharacter;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #36: Das Helden- und Charakterverzeichnis schlägt zufällige Plot-Charaktere vor.
 */
class PlotCharacterSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function characters(int $visible, int $hidden = 0): void
    {
        for ($i = 1; $i <= $visible; $i++) {
            PlotCharacter::create(['name' => "Gestalt {$i}"]);
        }
        for ($i = 1; $i <= $hidden; $i++) {
            PlotCharacter::create(['name' => "Verborgen {$i}", 'public_visible' => false]);
        }
    }

    public function test_startseite_zeigt_hoechstens_vier_sichtbare_vorschlaege(): void
    {
        $this->characters(6, 2);

        $response = $this->get(route('public.hero.search'))
            ->assertOk()
            ->assertSee('Helden- und Charakterverzeichnis')
            ->assertSee('Bekannten Gestalten begegnen')
            ->assertSee('Andere Gestalten zeigen')
            ->assertDontSee('Verborgen');

        $suggestions = $response->viewData('suggestions');
        $this->assertCount(4, $suggestions);
        $this->assertTrue($suggestions->every(fn ($c) => $c->public_visible));
        $this->assertSame(4, substr_count($response->getContent(), 'href="'.url('/h/plot/')));
    }

    public function test_ohne_sichtbare_plot_charaktere_gibt_es_keinen_abschnitt(): void
    {
        $this->characters(0, 1);

        $this->get(route('public.hero.search'))
            ->assertOk()
            ->assertDontSee('Bekannten Gestalten begegnen');
    }

    public function test_suche_ohne_treffer_und_zu_kurze_eingabe_zeigen_vorschlaege(): void
    {
        $this->characters(2);

        $this->get(route('public.hero.search.go', ['code' => 'Niemandsname']))
            ->assertOk()
            ->assertSee('Niemand mit diesem Namen gefunden.')
            ->assertSee('Bekannten Gestalten begegnen');

        $this->get(route('public.hero.search.go', ['code' => 'x']))
            ->assertOk()
            ->assertSee('Bekannten Gestalten begegnen');
    }

    public function test_bei_treffern_gibt_es_keine_vorschlaege(): void
    {
        $this->characters(2);
        Hero::factory()->create([
            'character_name' => 'Aldric',
            'public_visible' => true,
            'public_searchable' => true,
        ]);

        $this->get(route('public.hero.search.go', ['code' => 'Aldric']))
            ->assertOk()
            ->assertSee('Aldric')
            ->assertDontSee('Bekannten Gestalten begegnen');

        // Treffer bei einem Plot-Charakter ebenfalls ohne Vorschläge.
        $this->get(route('public.hero.search.go', ['code' => 'Gestalt 1']))
            ->assertOk()
            ->assertSee('Bekannte Gestalten')
            ->assertDontSee('Bekannten Gestalten begegnen');
    }
}
