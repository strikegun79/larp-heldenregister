<?php

namespace Tests\Feature;

use App\Models\PlotCharacter;
use App\Models\User;
use Database\Seeders\PlotCharacterSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #34: Erweiterter Steckbrief der Plot-Charaktere, Reiter „SL-Planung“
 * und Seeder mit den bekannten Persönlichkeiten Silvaniens.
 */
class PlotCharacterDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach($roleId);

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Der Schwarzmagier',
            'character_type' => 'nsc',
            'status' => 'banished',
            'species' => 'unknown',
            'alignment' => 'hostile',
            'public_visible' => '1',
        ], $overrides);
    }

    // ── Speichern ─────────────────────────────────────────────────────

    public function test_erweiterte_felder_listen_und_chronik_werden_gespeichert(): void
    {
        $this->actingAs($this->userWithRole(35))
            ->post(route('admin.plot-characters.store'), $this->validPayload([
                'class_name' => 'Magier',
                'first_seen' => 'Unbekannt (spätestens am 18.04.2026 erwähnt)',
                'abilities' => "Dunkle Magie\n\n  Verbindung zu einem Artefakt  \n",
                'artifacts' => 'Der dunkle Würfel',
                'chronicle' => "24.08.2024 | Kunde aus dem Waldritterlager | https://www.waldritter-giessen.de/brans-tagebuch/\n"
                    ."18.04.2026 | Ein Versteck für den Würfel\n"
                    ."Zahlreiche weitere Berichte\n"
                    .' | Ohne Datum | javascript:alert(1)',
                'public_lore' => 'Der Schwarzmagier wurde gebannt.',
                'gm_notes' => 'Rückkehr im Sommercamp geplant.',
            ]))
            ->assertRedirect(route('admin.plot-characters.index'));

        $character = PlotCharacter::firstOrFail();
        $this->assertSame('banished', $character->status);
        $this->assertSame('hostile', $character->alignment);
        $this->assertSame(['Dunkle Magie', 'Verbindung zu einem Artefakt'], $character->abilities);
        $this->assertSame(['Der dunkle Würfel'], $character->artifacts);
        $this->assertNull($character->known_titles);
        // assertEquals: MySQL-JSON sortiert die Schlüssel je Eintrag, die Reihenfolge ist egal.
        $this->assertEquals([
            ['date' => '24.08.2024', 'title' => 'Kunde aus dem Waldritterlager', 'url' => 'https://www.waldritter-giessen.de/brans-tagebuch/'],
            ['date' => '18.04.2026', 'title' => 'Ein Versteck für den Würfel', 'url' => null],
            ['date' => null, 'title' => 'Zahlreiche weitere Berichte', 'url' => null],
            // Nur http(s)-Links werden übernommen.
            ['date' => null, 'title' => 'Ohne Datum', 'url' => null],
        ], $character->chronicle);
        $this->assertSame('Rückkehr im Sommercamp geplant.', $character->gm_notes);
    }

    public function test_ungueltige_auswahlwerte_werden_abgelehnt(): void
    {
        $this->actingAs($this->userWithRole(35))
            ->post(route('admin.plot-characters.store'), $this->validPayload([
                'status' => 'zombie',
                'alignment' => 'evil',
            ]))
            ->assertSessionHasErrors(['status', 'alignment']);
    }

    public function test_chronik_text_laesst_sich_verlustfrei_bearbeiten(): void
    {
        $character = new PlotCharacter(['chronicle' => PlotCharacter::parseChronicle(
            "05.08.2018 | Tagebuch des Bran\nNur ein Titel\n | Ohne Datum | https://example.org/x"
        )]);

        $this->assertSame(
            $character->chronicle,
            PlotCharacter::parseChronicle($character->chronicleText())
        );
    }

    // ── Öffentliche Seite ─────────────────────────────────────────────

    private function schwarzmagier(): PlotCharacter
    {
        return PlotCharacter::create([
            'name' => 'Der Schwarzmagier',
            'status' => 'banished',
            'alignment' => 'hostile',
            'class_name' => 'Magier',
            'home' => 'Unbekannt',
            'artifacts' => ['Der dunkle Würfel'],
            'chronicle' => [['date' => '18.04.2026', 'title' => 'Ein Versteck für den Würfel', 'url' => null]],
            'quote' => 'Die Welt wird mir gehören!',
            'motto' => 'Ein gebannter Feind ist nicht zwangsläufig ein besiegter Feind.',
            'public_lore' => 'Sein Aufenthaltsort ist unbekannt.',
            'gm_notes' => 'GEHEIM: Rückkehr im Sommercamp.',
        ]);
    }

    public function test_oeffentliche_seite_zeigt_steckbrief_ohne_unbekannte_felder(): void
    {
        $this->get(route('public.plot-character', $this->schwarzmagier()))
            ->assertOk()
            ->assertSee('Gebannt')
            ->assertSee('Feindlich')
            ->assertSee('Magier')
            ->assertDontSee('Heimatort')
            ->assertSee('Der dunkle Würfel')
            ->assertSee('Ein Versteck für den Würfel')
            ->assertSee('Die Welt wird mir gehören!')
            ->assertSee('Ein gebannter Feind ist nicht zwangsläufig ein besiegter Feind.')
            ->assertSee('Was die Waldritter wissen')
            ->assertSee('Sein Aufenthaltsort ist unbekannt.');
    }

    public function test_sl_planung_ist_fuer_gaeste_und_teilnehmer_unsichtbar(): void
    {
        $character = $this->schwarzmagier();

        $this->get(route('public.plot-character', $character))
            ->assertOk()
            ->assertDontSee('SL-Planung')
            ->assertDontSee('GEHEIM: Rückkehr im Sommercamp.');

        foreach ([70, 50, 45] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))
                ->get(route('public.plot-character', $character))
                ->assertOk()
                ->assertDontSee('GEHEIM: Rückkehr im Sommercamp.');
        }
    }

    public function test_sl_planung_ist_fuer_berechtigte_rollen_sichtbar(): void
    {
        $character = $this->schwarzmagier();

        // Plot-Schreiber, Bürokrat, Projektleitung, Spielleiter, Admin.
        foreach ([35, 20, 30, 40, 10] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))
                ->get(route('public.plot-character', $character))
                ->assertOk()
                ->assertSee('SL-Planung')
                ->assertSee('GEHEIM: Rückkehr im Sommercamp.');
        }
    }

    public function test_suchkarte_zeigt_status(): void
    {
        $this->schwarzmagier();

        $this->get(route('public.hero.search.go', ['code' => 'Schwarz']))
            ->assertOk()
            ->assertSee('Gebannt');
    }

    // ── Seeder ────────────────────────────────────────────────────────

    public function test_seeder_legt_die_fuenf_charaktere_an(): void
    {
        $this->seed(PlotCharacterSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['babayaga', 'koenig-goldlocke', 'lehrmeister-bran', 'der-schwarzmagier', 'die-nekromantin'],
            PlotCharacter::pluck('slug')->all()
        );

        $bran = PlotCharacter::where('slug', 'lehrmeister-bran')->firstOrFail();
        $this->assertSame('Darum werde ich mich wohl wieder einmal selbst kümmern müssen!', $bran->quote);
        $this->assertCount(5, $bran->known_titles);
        $this->assertSame('deceased', PlotCharacter::where('slug', 'babayaga')->value('status'));
    }

    public function test_seeder_legt_nichts_doppelt_an_und_ueberschreibt_nichts(): void
    {
        $this->seed(PlotCharacterSeeder::class);
        PlotCharacter::where('slug', 'babayaga')->update(['title' => 'Geändert in der Verwaltung']);

        $this->seed(PlotCharacterSeeder::class);

        $this->assertSame(5, PlotCharacter::count());
        $this->assertSame('Geändert in der Verwaltung', PlotCharacter::where('slug', 'babayaga')->value('title'));
    }
}
