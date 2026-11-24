<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\PerlColor;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #25: Das Fertigkeiten-Modal zeigt die Perlenfarbe – im Heldenbuch
 * (angemeldet) und auf der öffentlichen Heldenseite.
 */
class SkillModalPerlColorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    /**
     * Held mit einer Klasse und zwei Fertigkeiten (mit und ohne Perlenfarbe).
     */
    private function heroWithSkills(): Hero
    {
        $color = PerlColor::create(['name' => 'Rubinrot', 'code' => '#cc0000']);
        $class = HeroClass::create(['id' => 990, 'name' => 'Perlklasse', 'slug' => 'perl-990', 'ep_cost' => 0]);
        $withColor = Skill::create(['name' => 'Rubinzauber', 'ep_costs' => 4, 'level' => 1, 'perl_count' => 1, 'perl_color_id' => $color->id]);
        $withoutColor = Skill::create(['name' => 'Farblos', 'ep_costs' => 2, 'level' => 1, 'perl_count' => 0]);
        $class->skills()->attach([$withColor->id, $withoutColor->id]);

        $hero = Hero::factory()->create(['public_code' => 'PERL25', 'public_visible' => true]);
        $hero->classes()->attach($class->id);

        return $hero;
    }

    public function test_heldenbuch_liefert_perlenfarbe_an_jede_fertigkeit(): void
    {
        $hero = $this->heroWithSkills();
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        $html = $this->actingAs($admin)
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('data-skill-perl-name="Rubinrot"', false)
            ->assertSee('data-skill-perl-code="#cc0000"', false)
            // Fertigkeit ohne Perlenfarbe: leere Attribute (Zeile im Modal bleibt verborgen).
            ->assertSee('data-skill-perl-name=""', false)
            ->getContent();

        // Spaltenansicht, Bild-Skilltree und Listenansicht tragen die Perlenfarbe –
        // jeweils in der Mobil- und der Desktop-Ausgabe des Heldenbuchs.
        $this->assertSame(6, substr_count($html, 'data-skill-perl-name="Rubinrot"'));
    }

    public function test_modal_im_angemeldeten_bereich_hat_perlenzeile(): void
    {
        $hero = $this->heroWithSkills();
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        $this->actingAs($admin)
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('id="skill-modal-perl"', false)
            ->assertSee('Perlenfarbe:');
    }

    public function test_oeffentliche_heldenseite_liefert_perlenfarbe_ans_modal(): void
    {
        $this->heroWithSkills();

        $html = $this->get(route('public.hero', 'PERL25'))
            ->assertOk()
            ->assertSee('data-skill-perl-name="Rubinrot"', false)
            ->assertSee('perlName: $el.dataset.skillPerlName', false)
            ->assertSee('x-text="skill.perlName"', false)
            ->getContent();

        // Spaltenansicht und Bild-Skilltree.
        $this->assertSame(2, substr_count($html, 'data-skill-perl-name="Rubinrot"'));
    }
}
