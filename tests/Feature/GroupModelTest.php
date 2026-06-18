<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Hero;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GRP-01: Gruppen-Schema + Model + Relationen.
 */
class GroupModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_group_can_be_created(): void
    {
        $group = Group::create([
            'name' => 'Die Rabenklaue',
            'description' => 'Eine gefürchtete Räuberbande.',
        ]);

        $this->assertDatabaseHas('groups', ['name' => 'Die Rabenklaue']);
        $this->assertNull($group->image);
    }

    public function test_group_factory_works(): void
    {
        $group = Group::factory()->create();

        $this->assertNotEmpty($group->name);
        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    public function test_group_can_have_heroes(): void
    {
        $group = Group::factory()->create();
        $hero = Hero::factory()->create();

        $group->heroes()->attach($hero->id, ['joined_at' => now()]);

        $this->assertCount(1, $group->fresh()->heroes);
        $this->assertEquals($hero->id, $group->heroes->first()->id);
    }

    public function test_hero_can_belong_to_multiple_groups(): void
    {
        $hero = Hero::factory()->create();
        $g1 = Group::factory()->create();
        $g2 = Group::factory()->create();

        $hero->groups()->attach([$g1->id, $g2->id]);

        $this->assertCount(2, $hero->fresh()->groups);
    }

    public function test_pivot_stores_role_and_joined_at(): void
    {
        $group = Group::factory()->create();
        $hero = Hero::factory()->create();

        $group->heroes()->attach($hero->id, [
            'role' => 'Anführer',
            'joined_at' => '2024-03-15 00:00:00',
        ]);

        $pivot = $group->heroes()->first()->pivot;
        $this->assertEquals('Anführer', $pivot->role);
        $this->assertEquals('2024-03-15', substr($pivot->joined_at, 0, 10));
    }

    public function test_deleting_group_removes_pivot_entries(): void
    {
        $group = Group::factory()->create();
        $hero = Hero::factory()->create();
        $group->heroes()->attach($hero->id);

        $group->delete();

        $this->assertDatabaseMissing('group_hero', ['group_id' => $group->id]);
    }

    public function test_deleting_hero_removes_pivot_entries(): void
    {
        $group = Group::factory()->create();
        $hero = Hero::factory()->create();
        $group->heroes()->attach($hero->id);

        $hero->delete();

        $this->assertDatabaseMissing('group_hero', ['hero_id' => $hero->id]);
    }
}
