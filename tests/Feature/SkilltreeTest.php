<?php

namespace Tests\Feature;

use App\Models\HeroClass;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\HeroClassSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkilltreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, HeroClassSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    private function skillForClass(int $classId): Skill
    {
        $skill = Skill::create(['name' => 'Probe-Fertigkeit', 'ep_costs' => 2, 'perl_count' => 0]);
        $skill->classes()->attach($classId);

        return $skill;
    }

    public function test_a_viewer_cannot_open_the_editor(): void
    {
        $this->actingAs($this->userWithRole(40)) // Spielleiter: kein heldenregister.edit
            ->get(route('skilltree.edit', HeroClass::find(1)))
            ->assertForbidden();
    }

    public function test_an_editor_sees_the_editor(): void
    {
        $skill = $this->skillForClass(1);

        $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->get(route('skilltree.edit', HeroClass::find(1)))
            ->assertOk()
            ->assertSee('Krieger')
            ->assertSee('editor-marker', false)
            ->assertSee($skill->name);
    }

    public function test_an_editor_saves_marker_positions(): void
    {
        $skill = $this->skillForClass(1);

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('skilltree.update', HeroClass::find(1)), [
                'positions' => [
                    ['skill_id' => $skill->id, 'x' => 25.5, 'y' => 60.2],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('skill_hero_class', [
            'skill_id' => $skill->id,
            'hero_class_id' => 1,
            'x_percentage' => 26, // gerundet
            'y_percentage' => 60,
        ]);
    }

    public function test_position_validation_rejects_out_of_range(): void
    {
        $skill = $this->skillForClass(1);

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('skilltree.update', HeroClass::find(1)), [
                'positions' => [
                    ['skill_id' => $skill->id, 'x' => 150, 'y' => 60],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_a_viewer_cannot_save_positions(): void
    {
        $skill = $this->skillForClass(1);

        $this->actingAs($this->userWithRole(40))
            ->patch(route('skilltree.update', HeroClass::find(1)), [
                'positions' => [['skill_id' => $skill->id, 'x' => 10, 'y' => 10]],
            ])
            ->assertForbidden();
    }
}
