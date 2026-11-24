<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #29: Der Bürokrat kann im Heldenbuch den Helden-Schwur der Klasse anzeigen.
 */
class HeroOathTest extends TestCase
{
    use RefreshDatabase;

    private Skill $ersteHilfe;

    private Skill $waffentraining;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);

        $this->ersteHilfe = Skill::create(['name' => 'Erste Hilfe', 'ep_costs' => 0, 'level' => 1, 'perl_count' => 0]);
        $this->waffentraining = Skill::create(['name' => 'Waffentraining', 'ep_costs' => 0, 'level' => 1, 'perl_count' => 0]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach($roleId);

        return $user;
    }

    /** Held der Klasse Krieger (mit Standard-Schwur); optional mit erlernten Fertigkeiten. */
    private function warrior(array $learnedSkills = []): Hero
    {
        $class = HeroClass::create(['id' => 1, 'name' => 'Krieger', 'slug' => 'warrior', 'ep_cost' => 0]);
        HeroClass::fillDefaultOaths();
        $class->skills()->attach([$this->ersteHilfe->id, $this->waffentraining->id]);

        $hero = Hero::factory()->create(['character_name' => 'Aldric']);
        $hero->classes()->attach($class->id);
        foreach ($learnedSkills as $skill) {
            $hero->skills()->attach($skill->id);
        }

        return $hero;
    }

    public function test_standard_schwuere_werden_fuer_die_fuenf_klassen_eingetragen(): void
    {
        foreach (['warrior' => 'Krieger', 'ranger' => 'Waldläufer', 'healer' => 'Heiler', 'wizard' => 'Magier', 'alchemist' => 'Alchemist'] as $slug => $name) {
            HeroClass::create(['id' => crc32($slug) % 1000, 'name' => $name, 'slug' => $slug, 'ep_cost' => 0]);
        }
        HeroClass::create(['id' => 999, 'name' => 'Eigene Klasse', 'slug' => 'custom', 'ep_cost' => 0]);
        HeroClass::where('slug', 'healer')->update(['oath_text' => 'Eigener Heiler-Schwur']);

        HeroClass::fillDefaultOaths();

        $this->assertSame('Schwur der Tapferkeit', HeroClass::where('slug', 'warrior')->value('oath_title'));
        $this->assertStringStartsWith('Ich schwöre, die Natur zu achten.', HeroClass::where('slug', 'ranger')->value('oath_text'));
        $this->assertStringContainsString('Hiermit seist du ein Alchemist!', HeroClass::where('slug', 'alchemist')->value('ceremony_text'));
        $this->assertSame('Schwur der Weisheit', HeroClass::where('slug', 'wizard')->value('oath_title'));
        // Vorhandene Texte bleiben, unbekannte Klassen bleiben leer.
        $this->assertSame('Eigener Heiler-Schwur', HeroClass::where('slug', 'healer')->value('oath_text'));
        $this->assertNull(HeroClass::where('slug', 'custom')->value('oath_text'));
    }

    public function test_buerokrat_sieht_schwur_und_feierliche_aufnahme(): void
    {
        $hero = $this->warrior();

        $html = $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('📜 Schwur der Tapferkeit')
            ->assertSee('Schwur – vom Bürokraten vorgesagt')
            ->assertSee('Ich schwöre, mutig und tapfer zu sein.')
            ->assertSee('Feierliche Aufnahme – Spielleitung')
            ->assertSee('Hiermit seist du ein Krieger!')
            ->getContent();

        // Mobil- und Desktop-Ausgabe mit eindeutigen Fenster-IDs.
        $this->assertStringContainsString('id="oath-m-1"', $html);
        $this->assertStringContainsString('id="oath-d-1"', $html);
    }

    public function test_andere_rollen_sehen_keinen_schwur(): void
    {
        $hero = $this->warrior();

        // Spielleiter, Lehrmeister, Projektleitung.
        foreach ([40, 45, 30] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))
                ->get(route('heroes.show', $hero))
                ->assertOk()
                ->assertDontSee('Schwur der Tapferkeit')
                ->assertDontSee('Ich schwöre, mutig und tapfer zu sein.');
        }
    }

    public function test_hinweis_nennt_fehlende_fertigkeiten(): void
    {
        $hero = $this->warrior([$this->ersteHilfe]);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('Vor der Bändchen-Übergabe fehlt noch:')
            ->assertSee('<strong>Waffentraining</strong>', false)
            ->assertDontSee('das Bändchen kann übergeben werden');
    }

    public function test_hinweis_bestaetigt_beide_fertigkeiten(): void
    {
        $hero = $this->warrior([$this->ersteHilfe, $this->waffentraining]);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('Erste Hilfe und Waffentraining erlernt – das Bändchen kann übergeben werden.')
            ->assertDontSee('fehlt noch');
    }

    public function test_klasse_ohne_schwur_hat_keinen_button(): void
    {
        $class = HeroClass::create(['id' => 77, 'name' => 'Barde', 'slug' => 'bard', 'ep_cost' => 0]);
        $hero = Hero::factory()->create();
        $hero->classes()->attach($class->id);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertDontSee('id="oath-', false);
    }

    public function test_schwur_laesst_sich_in_der_klassenverwaltung_speichern(): void
    {
        $class = HeroClass::create(['id' => 77, 'name' => 'Barde', 'slug' => 'bard', 'ep_cost' => 0]);

        $this->actingAs($this->userWithRole(10))
            ->put(route('admin.hero-classes.update', $class), [
                'name' => 'Barde',
                'slug' => 'bard',
                'ep_cost' => 0,
                'oath_title' => 'Schwur der Lieder',
                'oath_text' => "Ich schwöre, zu singen.\nFür Ehre, Freundschaft und die Waldritter!",
                'ceremony_text' => '„Hiermit seist du ein Barde!“',
            ])
            ->assertRedirect();

        $class->refresh();
        $this->assertSame('Schwur der Lieder', $class->oath_title);
        $this->assertTrue($class->hasOath());
        $this->assertSame('„Hiermit seist du ein Barde!“', $class->ceremony_text);
    }
}
