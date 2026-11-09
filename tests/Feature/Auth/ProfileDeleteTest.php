<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_destroy_soft_deletes_user_and_logs_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertGuest();

        // Soft-Delete: Datensatz noch in DB, aber deleted_at gesetzt
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_soft_deleted_user_cannot_login(): void
    {
        $user = User::factory()->create();
        $user->delete(); // soft-delete

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_admin_sees_soft_deleted_users_in_list(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->roles()->attach(10); // Admin-Rolle

        $deleted = User::factory()->create(['name' => 'Gelöschter Nutzer']);
        $deleted->delete();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Gelöschter Nutzer')
            ->assertSee('[gelöscht]');
    }

    public function test_admin_can_restore_soft_deleted_user(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        $deleted = User::factory()->create(['name' => 'Zu restaurieren']);
        $deleted->delete();

        $this->assertSoftDeleted('users', ['id' => $deleted->id]);

        $this->actingAs($admin)
            ->patch(route('admin.users.restore', $deleted->id))
            ->assertRedirect(route('admin.users.index'));

        $this->assertNotSoftDeleted('users', ['id' => $deleted->id]);
    }

    public function test_wrong_password_prevents_profile_deletion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'falsch'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }
}
