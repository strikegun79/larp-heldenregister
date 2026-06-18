<?php

namespace Tests\Feature;

use App\Models\HeroClass;
use App\Models\PerlColor;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKILL-04: Fertigkeiten-Katalog (read-only, nach Klasse).
 */
class SkillCatalogTest extends TestCase
{
    use RefreshDatabase;

    private HeroClass $class;

    private PerlColor $color;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->class = HeroClass::create(['id' => 1, 'name' => 'Krieger', 'slug' => 'warrior', 'disabled' => false]);
        $this->color = PerlColor::create(['code' => '#a2de00', 'name' => 'Grün']);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // registrar: heldenregister.view

        return $user;
    }

    private function skill(string $name, int $level = 1, ?int $ep = 10): Skill
    {
        $skill = Skill::create([
            'name' => $name,
            'level' => $level,
            'ep_costs' => $ep,
            'hero_class_id' => $this->class->id,
            'perl_color_id' => $this->color->id,
        ]);
        $skill->classes()->attach($this->class->id);

        return $skill;
    }

    public function test_catalog_is_accessible_with_heldenregister_view(): void
    {
        $this->skill('Schwertkunst');

        $this->actingAs($this->viewer())
            ->get(route('skills.catalog'))
            ->assertOk()
            ->assertSee('Fertigkeiten-Katalog')
            ->assertSee('Schwertkunst');
    }

    public function test_catalog_shows_class_name_as_heading(): void
    {
        $this->skill('Schildblock');

        $this->actingAs($this->viewer())
            ->get(route('skills.catalog'))
            ->assertOk()
            ->assertSee('Krieger');
    }

    public function test_catalog_shows_perl_color(): void
    {
        $this->skill('Bogenschuss');

        $this->actingAs($this->viewer())
            ->get(route('skills.catalog'))
            ->assertOk()
            ->assertSee('#a2de00', false)
            ->assertSee('Grün');
    }

    public function test_catalog_filter_by_class(): void
    {
        $other = HeroClass::create(['id' => 2, 'name' => 'Magier', 'slug' => 'mage', 'disabled' => false]);
        $skillKrieger = $this->skill('Sturmangriff');
        $skillMagier = Skill::create([
            'name' => 'Feuerball',
            'level' => 1,
            'ep_costs' => 15,
            'hero_class_id' => $other->id,
        ]);
        $skillMagier->classes()->attach($other->id);

        $response = $this->actingAs($this->viewer())
            ->get(route('skills.catalog', ['class_id' => $this->class->id]))
            ->assertOk();

        $response->assertSee('Sturmangriff');
        $response->assertDontSee('Feuerball');
    }

    public function test_catalog_requires_authentication(): void
    {
        $this->get(route('skills.catalog'))->assertRedirect(route('login'));
    }

    public function test_catalog_requires_heldenregister_view_permission(): void
    {
        $participant = User::factory()->create();
        $participant->roles()->attach(70); // participant: keine heldenregister.view

        $this->actingAs($participant)
            ->get(route('skills.catalog'))
            ->assertForbidden();
    }
}
