<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10); // admin

        return $admin;
    }

    public function test_non_admins_cannot_access_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admins_can_open_the_admin_area(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.players.index'))->assertOk();
    }

    public function test_an_admin_can_assign_roles_and_toggle_activation(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['activated' => true]);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'roles' => [20, 60], // registrar + event_booking
            // activated nicht gesetzt -> deaktivieren
        ])->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertEqualsCanonicalizing([20, 60], $target->roles->pluck('id')->all());
        $this->assertFalse($target->activated);
    }

    public function test_a_non_admin_cannot_update_users(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.users.update', $target), ['roles' => [10]])
            ->assertForbidden();

        $this->assertCount(0, $target->fresh()->roles);
    }
}
