<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Heldenbuch im Waldritter-Design (Seite und Modal): Seitenkopf, Schatzkiste,
 * Fortschritt, öffentliches Profil, eindeutige IDs trotz doppelter Einbindung.
 */
class HeroDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Elternteil/Betreuer mit eigenem Spieler und Held. */
    private function ownerWithHero(array $heroAttributes = []): array
    {
        $owner = User::factory()->create();
        $owner->roles()->attach(70);
        $player = Player::factory()->create();
        $owner->players()->attach($player->id, ['self' => false]);
        $hero = Hero::factory()->for($player)->create(array_merge(['character_name' => 'Ritter Kunibert'], $heroAttributes));

        return [$owner, $hero];
    }

    public function test_full_page_has_heldenbuch_heading_and_back_link_for_owner(): void
    {
        [$owner, $hero] = $this->ownerWithHero();

        $this->actingAs($owner)->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('Heldenbuch')
            ->assertSee('<h1 class="wr-heading', false)
            ->assertSee('Ritter Kunibert')
            ->assertSee('Zurück zu meinen Spielern')
            ->assertDontSee('Zurück zum Register');
    }

    public function test_overview_shows_treasure_and_progress(): void
    {
        [$owner, $hero] = $this->ownerWithHero();

        $this->actingAs($owner)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSee('Verfügbare Erfahrungspunkte')
            ->assertSee('0 Fertigkeiten')
            ->assertSee('0 Abenteuer bestritten')
            ->assertSee('Was sind EP?')
            ->assertSee('data-tab="overview"', false);
    }

    public function test_photo_upload_ids_are_unique_for_mobile_and_desktop(): void
    {
        [$owner, $hero] = $this->ownerWithHero();

        $html = $this->actingAs($owner)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'id="hero-photo-file-input-m"'));
        $this->assertSame(1, substr_count($html, 'id="hero-photo-file-input-d"'));
        $this->assertStringContainsString('Foto ändern', $html);
    }

    public function test_public_profile_section_for_owner(): void
    {
        [$owner, $hero] = $this->ownerWithHero(['public_code' => 'SIEGEL1', 'public_visible' => false]);

        $this->actingAs($owner)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Öffentliches Profil und Ausweis')
            ->assertSee('SIEGEL1')
            ->assertSee('versteckt')
            ->assertSee('Profil öffentlich sichtbar')
            ->assertSee('Ausweis (PDF)');
    }

    public function test_status_is_text_not_only_color(): void
    {
        [$owner, $hero] = $this->ownerWithHero(['died' => '2025-05-01', 'active' => false]);

        $this->actingAs($owner)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('verschollen seit 01.05.2025');
    }

    public function test_skill_states_have_accessible_labels_symbols_and_buttons(): void
    {
        [$owner, $hero] = $this->ownerWithHero();
        $class = HeroClass::create(['id' => 990, 'name' => 'Waldkunde', 'slug' => 'test-990', 'ep_cost' => 0]);
        $learned = Skill::create(['name' => 'Spurenlesen', 'ep_costs' => 2, 'level' => 1, 'perl_count' => 0]);
        $locked = Skill::create(['name' => 'Fallenstellen', 'ep_costs' => 4, 'level' => 2, 'perl_count' => 0]);
        $prereq = Skill::create(['name' => 'Tarnung', 'ep_costs' => 3, 'level' => 1, 'perl_count' => 0]);
        $locked->prerequisites()->attach($prereq->id, ['hero_class_id' => $class->id]);
        $class->skills()->attach([$learned->id, $locked->id, $prereq->id]);
        $hero->classes()->attach($class->id);
        $hero->skills()->attach($learned->id);

        $html = $this->actingAs($owner)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('aria-label="Spurenlesen, 2 EP, erlernt"', false)
            ->assertSee('aria-label="Fallenstellen, 4 EP, gesperrt"', false)
            ->assertSee('aria-label="Tarnung, 3 EP, noch nicht erlernt"', false)
            ->assertSee('gesperrt (erst Voraussetzungen lernen)')
            ->assertSee('aria-pressed', false)
            ->getContent();

        // Alle Auslöser des Lern-Dialogs sind echte Buttons (Tastatur), keine Emojis als einzige Kennzeichnung.
        $this->assertDoesNotMatchRegularExpression('/<(a|div)[^>]*class="[^"]*skill-trigger/', $html);
        $this->assertStringNotContainsString('🔒', $html);
    }
}
