<?php

namespace Tests\Feature;

use App\Models\HeroClass;
use App\Models\PerlColor;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillAdminTest extends TestCase
{
    use RefreshDatabase;

    private HeroClass $class;

    private PerlColor $color;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->class = HeroClass::create(['id' => 1, 'name' => 'Krieger', 'slug' => 'warrior', 'disabled' => false]);
        $this->color = PerlColor::create(['code' => '#FF0000', 'name' => 'rot']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        return $admin;
    }

    private function skillData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Testfertigkeit',
            'description' => 'Beschreibung',
            'ep_costs' => 3,
            'level' => 2,
            'hero_class_id' => $this->class->id,
            'perl_color_id' => $this->color->id,
            'perl_count' => 2,
            'classes' => [$this->class->id],
        ], $overrides);
    }

    public function test_admin_can_list_skills(): void
    {
        Skill::create([
            'name' => 'Schwerthieb', 'ep_costs' => 1, 'level' => 1,
            'hero_class_id' => $this->class->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.skills.index'))
            ->assertOk()
            ->assertSee('Schwerthieb');
    }

    public function test_admin_can_create_a_skill_with_class_assignment(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.skills.store'), $this->skillData())
            ->assertOk()
            ->assertJson(['reload' => true]);

        $skill = Skill::where('name', 'Testfertigkeit')->first();
        $this->assertNotNull($skill);
        $this->assertEquals(3, $skill->ep_costs);
        $this->assertEquals(2, $skill->level);
        $this->assertCount(1, $skill->classes);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.skills.store'), $this->skillData(['name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_admin_can_update_a_skill(): void
    {
        $skill = Skill::create([
            'name' => 'Alt', 'ep_costs' => 1, 'level' => 1,
            'hero_class_id' => $this->class->id,
        ]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.skills.update', $skill), $this->skillData(['name' => 'Neu', 'ep_costs' => 5]))
            ->assertOk()
            ->assertJson(['reload' => true]);

        $this->assertDatabaseHas('skills', ['id' => $skill->id, 'name' => 'Neu', 'ep_costs' => 5]);
    }

    public function test_class_assignment_is_synced_on_update(): void
    {
        $skill = Skill::create(['name' => 'Sync', 'ep_costs' => 0, 'level' => 1, 'hero_class_id' => $this->class->id]);
        $class2 = HeroClass::create(['id' => 2, 'name' => 'Magier', 'slug' => 'wizard', 'disabled' => false]);
        $skill->classes()->attach($this->class->id);

        $this->actingAs($this->admin())
            ->putJson(route('admin.skills.update', $skill), $this->skillData(['classes' => [$class2->id]]))
            ->assertOk();

        $this->assertEqualsCanonicalizing([$class2->id], $skill->fresh()->classes->pluck('id')->all());
    }

    public function test_unused_skill_can_be_deleted(): void
    {
        $skill = Skill::create([
            'name' => 'Weg', 'ep_costs' => 0, 'level' => 1,
            'hero_class_id' => $this->class->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.skills.destroy', $skill))
            ->assertRedirect(route('admin.skills.index'));

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }

    public function test_non_admin_cannot_manage_skills(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.skills.index'))
            ->assertForbidden();
    }

    public function test_index_filters_by_class(): void
    {
        $class2 = HeroClass::create(['id' => 2, 'name' => 'Magier', 'slug' => 'wizard', 'disabled' => false]);
        Skill::create(['name' => 'Krieger-Skill', 'ep_costs' => 1, 'level' => 1, 'hero_class_id' => $this->class->id]);
        Skill::create(['name' => 'Magier-Skill', 'ep_costs' => 1, 'level' => 1, 'hero_class_id' => $class2->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.skills.index', ['class_id' => $this->class->id]))
            ->assertOk()
            ->assertSee('Krieger-Skill')
            ->assertDontSee('Magier-Skill');
    }
}
