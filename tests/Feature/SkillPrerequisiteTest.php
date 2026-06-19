<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillPrerequisiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10);

        return $user;
    }

    private function skill(array $attrs = []): Skill
    {
        return Skill::factory()->create($attrs);
    }

    // ── Relation ──────────────────────────────────────────────────────────────

    public function test_skill_can_have_prerequisites(): void
    {
        $base = $this->skill();
        $advanced = $this->skill();

        $advanced->prerequisites()->attach($base->id);

        $this->assertCount(1, $advanced->prerequisites);
        $this->assertEquals($base->id, $advanced->prerequisites->first()->id);
    }

    public function test_prerequisite_for_inverse_relation(): void
    {
        $base = $this->skill();
        $advanced = $this->skill();

        $advanced->prerequisites()->attach($base->id);

        $this->assertCount(1, $base->prerequisiteFor);
        $this->assertEquals($advanced->id, $base->prerequisiteFor->first()->id);
    }

    // ── Store: keine Zyklen möglich (neue Fertigkeit) ─────────────────────────

    public function test_admin_can_create_skill_with_prerequisites(): void
    {
        $base = $this->skill();

        $this->actingAs($this->admin())
            ->postJson(route('admin.skills.store'), [
                'name' => 'Fortgeschritten',
                'ep_costs' => 10,
                'level' => 2,
                'prerequisites' => [$base->id],
            ])
            ->assertOk();

        $advanced = Skill::where('name', 'Fortgeschritten')->first();
        $this->assertNotNull($advanced);
        $this->assertCount(1, $advanced->prerequisites);
    }

    // ── Update: Voraussetzungen speichern ─────────────────────────────────────

    public function test_admin_can_update_prerequisites(): void
    {
        $base = $this->skill();
        $advanced = $this->skill();

        $this->actingAs($this->admin())
            ->putJson(route('admin.skills.update', $advanced), [
                'name' => $advanced->name,
                'ep_costs' => $advanced->ep_costs,
                'level' => $advanced->level,
                'prerequisites' => [$base->id],
            ])
            ->assertOk();

        $this->assertCount(1, $advanced->fresh()->prerequisites);
    }

    public function test_prerequisites_can_be_cleared(): void
    {
        $base = $this->skill();
        $advanced = $this->skill();
        $advanced->prerequisites()->attach($base->id);

        $this->actingAs($this->admin())
            ->putJson(route('admin.skills.update', $advanced), [
                'name' => $advanced->name,
                'ep_costs' => $advanced->ep_costs,
                'level' => $advanced->level,
                'prerequisites' => [],
            ])
            ->assertOk();

        $this->assertCount(0, $advanced->fresh()->prerequisites);
    }

    // ── Zyklen-Erkennung ──────────────────────────────────────────────────────

    public function test_direct_cycle_is_rejected(): void
    {
        // A benötigt B, B soll A benötigen → Zyklus
        $a = $this->skill();
        $b = $this->skill();
        $b->prerequisites()->attach($a->id);

        $this->actingAs($this->admin())
            ->putJson(route('admin.skills.update', $a), [
                'name' => $a->name,
                'ep_costs' => $a->ep_costs,
                'level' => $a->level,
                'prerequisites' => [$b->id],
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Voraussetzungen würden einen Kreisbezug erzeugen.']);
    }

    public function test_transitive_cycle_is_rejected(): void
    {
        // A → B → C; C soll A benötigen → Zyklus
        $a = $this->skill();
        $b = $this->skill();
        $c = $this->skill();
        $b->prerequisites()->attach($a->id);
        $c->prerequisites()->attach($b->id);

        $this->actingAs($this->admin())
            ->putJson(route('admin.skills.update', $a), [
                'name' => $a->name,
                'ep_costs' => $a->ep_costs,
                'level' => $a->level,
                'prerequisites' => [$c->id],
            ])
            ->assertStatus(422);
    }

    public function test_no_cycle_for_parallel_prerequisites(): void
    {
        // A und B sind unabhängig; C benötigt beide → kein Zyklus
        $a = $this->skill();
        $b = $this->skill();
        $c = $this->skill();

        $this->actingAs($this->admin())
            ->putJson(route('admin.skills.update', $c), [
                'name' => $c->name,
                'ep_costs' => $c->ep_costs,
                'level' => $c->level,
                'prerequisites' => [$a->id, $b->id],
            ])
            ->assertOk();

        $this->assertCount(2, $c->fresh()->prerequisites);
    }

    // ── Löschen entfernt Pivot-Einträge ───────────────────────────────────────

    public function test_deleting_prerequisite_skill_removes_pivot(): void
    {
        $base = $this->skill();
        $advanced = $this->skill();
        $advanced->prerequisites()->attach($base->id);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.skills.destroy', $base))
            ->assertOk();

        $this->assertDatabaseMissing('skill_prerequisites', [
            'skill_id' => $advanced->id,
            'required_skill_id' => $base->id,
        ]);
    }
}
