<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PlotCharacter;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Issue #30: Bekannte Plot-Charaktere – Verwaltung, öffentliche Suche unter /h
 * und öffentliche Detailseite /h/plot/{slug}.
 */
class PlotCharacterTest extends TestCase
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

    // ── Berechtigungen ────────────────────────────────────────────────

    public function test_plot_schreiber_projektleitung_buerokrat_und_admin_duerfen_verwalten(): void
    {
        foreach ([35, 30, 20, 10] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))
                ->get(route('admin.plot-characters.index'))
                ->assertOk();
        }
    }

    public function test_andere_rollen_duerfen_nicht_verwalten(): void
    {
        foreach ([40, 45, 50, 60, 70] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))
                ->get(route('admin.plot-characters.index'))
                ->assertForbidden();
        }
    }

    public function test_plot_schreiber_sieht_verwaltung_mit_plot_card(): void
    {
        $this->actingAs($this->userWithRole(35))
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Plot-Charaktere')
            ->assertSee('href="'.route('admin.plot-characters.index').'"', false)
            // Keine fremden Verwaltungsbereiche.
            ->assertDontSee(route('admin.users.index'));
    }

    public function test_spielleiter_sieht_keine_plot_card(): void
    {
        $this->actingAs($this->userWithRole(40))
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee(route('admin.plot-characters.index'));
    }

    // ── CRUD ──────────────────────────────────────────────────────────

    public function test_plot_charakter_anlegen_mit_bild(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userWithRole(35))
            ->post(route('admin.plot-characters.store'), [
                'name' => 'König Goldlocke',
                'title' => 'Herrscher des Goldenen Tals',
                'description' => 'Ein gütiger König.',
                'public_visible' => '1',
                'image' => UploadedFile::fake()->image('koenig.jpg', 800, 600),
            ])
            ->assertRedirect(route('admin.plot-characters.index'));

        $character = PlotCharacter::firstOrFail();
        $this->assertSame('koenig-goldlocke', $character->slug);
        $this->assertTrue($character->public_visible);
        Storage::disk('public')->assertExists($character->image_path);
        $this->assertTrue(AuditLog::where('action', 'plot_character.created')->exists());
    }

    public function test_slug_wird_bei_gleichem_namen_eindeutig(): void
    {
        PlotCharacter::create(['name' => 'Bran']);
        $second = PlotCharacter::create(['name' => 'Bran']);

        $this->assertSame('bran-2', $second->slug);
    }

    public function test_name_ist_pflicht(): void
    {
        $this->actingAs($this->userWithRole(35))
            ->post(route('admin.plot-characters.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_plot_charakter_bearbeiten_und_bild_entfernen(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('plot-characters/alt.jpg', 'x');
        $character = PlotCharacter::create(['name' => 'Babayaga', 'image_path' => 'plot-characters/alt.jpg']);

        $this->actingAs($this->userWithRole(30))
            ->putJson(route('admin.plot-characters.update', $character), [
                'name' => 'Babayaga',
                'title' => 'Die Hexe im Hühnerbeinhaus',
                'public_visible' => '0',
                'remove_image' => '1',
            ])
            ->assertOk()
            ->assertJson(['reload' => true]);

        $character->refresh();
        $this->assertSame('Die Hexe im Hühnerbeinhaus', $character->title);
        $this->assertFalse($character->public_visible);
        $this->assertNull($character->image_path);
        Storage::disk('public')->assertMissing('plot-characters/alt.jpg');
    }

    public function test_plot_charakter_loeschen(): void
    {
        $character = PlotCharacter::create(['name' => 'Die Necromantin']);

        $this->actingAs($this->userWithRole(20))
            ->deleteJson(route('admin.plot-characters.destroy', $character))
            ->assertOk();

        $this->assertModelMissing($character);
        $this->assertTrue(AuditLog::where('action', 'plot_character.deleted')->exists());
    }

    public function test_bearbeiten_formular_im_inspektor_zeigt_loeschen(): void
    {
        $character = PlotCharacter::create(['name' => 'Der Schwarzmagier']);

        $this->actingAs($this->userWithRole(35))
            ->getJson(route('admin.plot-characters.edit', $character).'?inspector=1')
            ->assertOk()
            ->assertSee('Plot-Charakter löschen');
    }

    // ── Öffentliches Verzeichnis ──────────────────────────────────────

    public function test_suche_findet_sichtbare_plot_charaktere_nach_name_und_titel(): void
    {
        PlotCharacter::create(['name' => 'Babayaga', 'title' => 'Hexe des Nebelwalds']);
        PlotCharacter::create(['name' => 'Bran', 'title' => 'Chronist']);

        $this->get(route('public.hero.search.go', ['code' => 'baba']))
            ->assertOk()
            ->assertSee('Bekannte Gestalten')
            ->assertSee('Babayaga')
            ->assertSee(route('public.plot-character', 'babayaga'))
            ->assertDontSee('Chronist');

        $this->get(route('public.hero.search.go', ['code' => 'Chronist']))
            ->assertOk()
            ->assertSee('Bran');
    }

    public function test_suche_zeigt_keine_verborgenen_plot_charaktere(): void
    {
        PlotCharacter::create(['name' => 'Geheimer Bösewicht', 'public_visible' => false]);

        $this->get(route('public.hero.search.go', ['code' => 'Geheimer']))
            ->assertOk()
            ->assertDontSee('Geheimer Bösewicht')
            ->assertSee('Niemand mit diesem Namen gefunden.');
    }

    public function test_detailseite_ohne_login_erreichbar(): void
    {
        $character = PlotCharacter::create([
            'name' => 'König Goldlocke',
            'title' => 'Herrscher des Goldenen Tals',
            'description' => 'Ein gütiger König mit goldenem Haar.',
        ]);

        $this->get(route('public.plot-character', $character))
            ->assertOk()
            ->assertSee('König Goldlocke')
            ->assertSee('Herrscher des Goldenen Tals')
            ->assertSee('Ein gütiger König mit goldenem Haar.');
    }

    public function test_detailseite_verborgener_plot_charaktere_liefert_404(): void
    {
        $character = PlotCharacter::create(['name' => 'Versteckt', 'public_visible' => false]);

        $this->get(route('public.plot-character', $character))->assertNotFound();
    }

    public function test_dashboard_verlinkt_auf_das_verzeichnis(): void
    {
        $this->actingAs($this->userWithRole(70))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Heldenverzeichnis')
            ->assertSee('href="'.route('public.hero.search').'"', false);
    }
}
