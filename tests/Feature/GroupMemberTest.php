<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Hero;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_admin_can_view_members_modal(): void
    {
        $group = Group::factory()->create();

        $this->actingAs($this->userWithRole(10)) // Admin
            ->getJson(route('admin.groups.members', $group))
            ->assertOk()
            ->assertViewIs('admin.groups._members');
    }

    public function test_admin_can_add_hero_to_group(): void
    {
        $group = Group::factory()->create();
        $hero  = Hero::factory()->create();

        $this->actingAs($this->userWithRole(10))
            ->postJson(route('admin.groups.members.store', $group), ['hero_id' => $hero->id])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertTrue($group->heroes()->whereKey($hero->id)->exists());
    }

    public function test_admin_can_add_hero_with_role(): void
    {
        $group = Group::factory()->create();
        $hero  = Hero::factory()->create();

        $this->actingAs($this->userWithRole(10))
            ->postJson(route('admin.groups.members.store', $group), [
                'hero_id' => $hero->id,
                'role'    => 'Anführer',
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_hero', [
            'group_id' => $group->id,
            'hero_id'  => $hero->id,
            'role'     => 'Anführer',
        ]);
    }

    public function test_cannot_add_hero_twice(): void
    {
        $group = Group::factory()->create();
        $hero  = Hero::factory()->create();
        $group->heroes()->attach($hero->id, ['joined_at' => now()]);

        $this->actingAs($this->userWithRole(10))
            ->postJson(route('admin.groups.members.store', $group), ['hero_id' => $hero->id])
            ->assertStatus(422);

        $this->assertSame(1, $group->heroes()->count());
    }

    public function test_admin_can_remove_hero_from_group(): void
    {
        $group = Group::factory()->create();
        $hero  = Hero::factory()->create();
        $group->heroes()->attach($hero->id, ['joined_at' => now()]);

        $this->actingAs($this->userWithRole(10))
            ->deleteJson(route('admin.groups.members.destroy', [$group, $hero]))
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertFalse($group->heroes()->whereKey($hero->id)->exists());
    }

    public function test_cannot_remove_hero_not_in_group(): void
    {
        $group = Group::factory()->create();
        $hero  = Hero::factory()->create();

        $this->actingAs($this->userWithRole(10))
            ->deleteJson(route('admin.groups.members.destroy', [$group, $hero]))
            ->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_manage_members(): void
    {
        $group = Group::factory()->create();
        $hero  = Hero::factory()->create();

        // Teamer (50) hat kein groups.manage
        $this->actingAs($this->userWithRole(50))
            ->postJson(route('admin.groups.members.store', $group), ['hero_id' => $hero->id])
            ->assertForbidden();
    }
}
