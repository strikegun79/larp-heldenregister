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

/** SKILL-07: Spaltenansicht des Fertigkeitsbaums je Klasse. */
class HeroSkillTreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        return $user;
    }

    public function test_skill_spaltenansicht_zeigt_skill_name(): void
    {
        $admin = $this->admin();
        $class = HeroClass::create(['id' => 999, 'name' => 'Testklasse', 'slug' => 'test-999', 'ep_cost' => 0]);
        $skill = Skill::create(['name' => 'Feuerball', 'ep_costs' => 5, 'level' => 1, 'perl_count' => 0]);
        $class->skills()->attach($skill->id);
        $hero  = Hero::factory()->create();
        $hero->classes()->attach($class->id);

        $this->actingAs($admin)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('Feuerball');
    }

    public function test_gelernte_fertigkeit_ist_als_gelernt_markiert(): void
    {
        $admin = $this->admin();
        $class = HeroClass::create(['id' => 998, 'name' => 'Testklasse2', 'slug' => 'test-998', 'ep_cost' => 0]);
        $skill = Skill::create(['name' => 'Heilung', 'ep_costs' => 3, 'level' => 1, 'perl_count' => 0]);
        $class->skills()->attach($skill->id);
        $hero  = Hero::factory()->create();
        $hero->classes()->attach($class->id);
        $hero->skills()->attach($skill->id);

        // In der Baum-Ansicht (existierend) erscheint das ✓-Zeichen in der skill-list.
        $this->actingAs($admin)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('data-skill-learned="1"', false);
    }

    public function test_gesperrte_fertigkeit_hat_locked_attribute(): void
    {
        $admin      = $this->admin();
        $class      = HeroClass::create(['id' => 997, 'name' => 'Testklasse3', 'slug' => 'test-997', 'ep_cost' => 0]);
        $prereq     = Skill::create(['name' => 'Voraussetzung', 'ep_costs' => 2, 'level' => 1, 'perl_count' => 0]);
        $dependent  = Skill::create(['name' => 'Gesperrter Skill', 'ep_costs' => 5, 'level' => 2, 'perl_count' => 0]);
        $dependent->prerequisites()->attach($prereq->id);
        $class->skills()->attach([$prereq->id, $dependent->id]);
        $hero = Hero::factory()->create();
        $hero->classes()->attach($class->id);
        // Prereq NICHT gelernt → dependent muss gesperrt sein

        $this->actingAs($admin)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('data-skill-locked="1"', false);
    }

    public function test_skill_mit_perlfarbe_zeigt_farbcode(): void
    {
        $admin = $this->admin();
        $color = PerlColor::create(['name' => 'Rubinrot', 'code' => '#cc0000']);
        $class = HeroClass::create(['id' => 996, 'name' => 'Testklasse4', 'slug' => 'test-996', 'ep_cost' => 0]);
        $skill = Skill::create(['name' => 'Rubinzauber', 'ep_costs' => 4, 'level' => 1, 'perl_count' => 1, 'perl_color_id' => $color->id]);
        $class->skills()->attach($skill->id);
        $hero  = Hero::factory()->create();
        $hero->classes()->attach($class->id);

        $this->actingAs($admin)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('#cc0000', false);
    }

    public function test_skill_columns_partial_gruppiert_nach_level(): void
    {
        $class  = HeroClass::create(['id' => 995, 'name' => 'Testklasse5', 'slug' => 'test-995', 'ep_cost' => 0]);
        $skill1 = Skill::create(['name' => 'Skill Stufe 1', 'ep_costs' => 2, 'level' => 1, 'perl_count' => 0]);
        $skill2 = Skill::create(['name' => 'Skill Stufe 2', 'ep_costs' => 4, 'level' => 2, 'perl_count' => 0]);
        $class->skills()->attach([$skill1->id, $skill2->id]);
        $class->load('skills.prerequisites', 'skills.perlColor');

        $learnedIds = collect();

        $rendered = $this->blade(
            "@include('heroes.partials._skill_columns', ['class' => \$class, 'learnedIds' => \$learnedIds])",
            ['class' => $class, 'learnedIds' => $learnedIds]
        );

        $rendered->assertSee('Stufe 1');
        $rendered->assertSee('Stufe 2');
        $rendered->assertSee('Skill Stufe 1');
        $rendered->assertSee('Skill Stufe 2');
    }

    public function test_view_toggle_buttons_werden_gerendert(): void
    {
        $admin = $this->admin();
        $class = HeroClass::create(['id' => 994, 'name' => 'Testklasse6', 'slug' => 'test-994', 'ep_cost' => 0]);
        $skill = Skill::create(['name' => 'Testskill', 'ep_costs' => 1, 'level' => 1, 'perl_count' => 0]);
        $class->skills()->attach($skill->id);
        $hero  = Hero::factory()->create();
        $hero->classes()->attach($class->id);

        $this->actingAs($admin)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('Stufen')
             ->assertSee('Baum');
    }
}
