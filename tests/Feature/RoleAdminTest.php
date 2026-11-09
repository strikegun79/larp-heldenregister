<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAdminTest extends TestCase
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

    public function test_admin_can_view_roles_list(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Admin')
            ->assertSee('admin')
            ->assertSee('* (alle Rechte)');
    }

    public function test_roles_list_shows_permissions_and_user_count(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Bürokrat')
            ->assertSee('registrar')
            ->assertSee('events.edit')   // Bürokrat-Berechtigung
            ->assertSee('Teilnehmer');
    }

    public function test_non_admin_cannot_view_roles(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(70); // Teilnehmer

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_roles(): void
    {
        $this->get(route('admin.roles.index'))
            ->assertRedirect(route('login'));
    }
}
