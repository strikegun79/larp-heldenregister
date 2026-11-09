<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\PerlColor;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EP-07: Perlen-Übersicht je Held (Bändchen-Liste).
 */
class HeroPerlSummaryTest extends TestCase
{
    use RefreshDatabase;

    private PerlColor $red;

    private PerlColor $blue;

    private HeroClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->red = PerlColor::create(['code' => '#ff0000', 'name' => 'Rot']);
        $this->blue = PerlColor::create(['code' => '#0000ff', 'name' => 'Blau']);
        $this->class = HeroClass::create(['id' => 1, 'name' => 'Krieger', 'slug' => 'warrior', 'disabled' => false]);
    }

    private function skill(string $name, PerlColor $color): Skill
    {
        return Skill::create([
            'name' => $name,
            'level' => 1,
            'ep_costs' => 5,
            'hero_class_id' => $this->class->id,
            'perl_color_id' => $color->id,
        ]);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20);

        return $user;
    }

    public function test_perl_summary_aggregates_by_color(): void
    {
        $hero = Hero::factory()->create();
        $hero->skills()->attach($this->skill('Schwert', $this->red)->id, ['trained_at' => now()]);
        $hero->skills()->attach($this->skill('Schild', $this->red)->id, ['trained_at' => now()]);
        $hero->skills()->attach($this->skill('Magie', $this->blue)->id, ['trained_at' => now()]);

        $summary = $hero->perl_summary;

        $this->assertCount(2, $summary);

        $byName = $summary->keyBy('color.name');
        $this->assertEquals(2, $byName['Rot']->count);
        $this->assertEquals(1, $byName['Blau']->count);
    }

    public function test_perl_summary_is_empty_for_hero_without_skills(): void
    {
        $hero = Hero::factory()->create();

        $this->assertCount(0, $hero->perl_summary);
    }

    public function test_perl_summary_ignores_skills_without_color(): void
    {
        $hero = Hero::factory()->create();
        $skillNoColor = Skill::create([
            'name' => 'Laufen',
            'level' => 1,
            'ep_costs' => 0,
            'hero_class_id' => $this->class->id,
            'perl_color_id' => null,
        ]);
        $hero->skills()->attach($skillNoColor->id, ['trained_at' => now()]);
        $hero->skills()->attach($this->skill('Schwert', $this->red)->id, ['trained_at' => now()]);

        $summary = $hero->perl_summary;

        $this->assertCount(1, $summary);
        $this->assertEquals('Rot', $summary->first()->color->name);
    }

    public function test_perl_summary_sorted_by_color_name(): void
    {
        $hero = Hero::factory()->create();
        $hero->skills()->attach($this->skill('Schwert', $this->red)->id, ['trained_at' => now()]);
        $hero->skills()->attach($this->skill('Magie', $this->blue)->id, ['trained_at' => now()]);

        $names = $hero->perl_summary->pluck('color.name')->toArray();

        $this->assertEquals(['Blau', 'Rot'], $names);
    }

    public function test_modal_shows_perl_summary_table(): void
    {
        $hero = Hero::factory()->create();
        $hero->skills()->attach($this->skill('Schwert', $this->red)->id, ['trained_at' => now()]);
        $hero->skills()->attach($this->skill('Schild', $this->red)->id, ['trained_at' => now()]);

        $this->actingAs($this->viewer())
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Bändchen / Perlen')
            ->assertSee('Rot')
            ->assertSee('#ff0000', false);
    }

    public function test_modal_hides_perl_table_when_no_skills(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->viewer())
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('Bändchen / Perlen');
    }
}
