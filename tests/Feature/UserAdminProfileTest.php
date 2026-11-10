<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin-Profilverwaltung (AUTH-14): Profilansicht, Passwort, Benachrichtigungen,
 * Wiederherstellung soft-gelöschter Nutzer (T-1).
 */
class UserAdminProfileTest extends TestCase
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
        $admin->roles()->attach(10);

        return $admin;
    }

    public function test_admin_can_view_user_profile(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create(['name' => 'Aragon', 'email' => 'aragon@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.profile', $target))
            ->assertOk()
            ->assertSee('Aragon');
    }

    public function test_admin_can_update_user_profile(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.profile.update', $target), [
                'name'     => 'Legolas',
                'lastname' => 'Waldelf',
                'email'    => 'legolas@example.com',
            ])
            ->assertRedirect(route('admin.users.profile', $target));

        $target->refresh();
        $this->assertSame('Legolas', $target->name);
        $this->assertSame('legolas@example.com', $target->email);
    }

    public function test_profile_update_resets_email_verification_on_email_change(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($admin)
            ->patch(route('admin.users.profile.update', $target), [
                'name'  => $target->name,
                'email' => 'new@example.com',
            ]);

        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_profile_update_rejects_duplicate_email(): void
    {
        $admin    = $this->admin();
        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $target   = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.profile.update', $target), [
                'name'  => 'Test',
                'email' => 'taken@example.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_change_user_password(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.password.update', $target), [
                'password'              => 'NeuesPasswort123!',
                'password_confirmation' => 'NeuesPasswort123!',
            ])
            ->assertRedirect(route('admin.users.profile', $target));

        $this->assertTrue(Hash::check('NeuesPasswort123!', $target->fresh()->password));
    }

    public function test_password_change_requires_confirmation(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.password.update', $target), [
                'password'              => 'NeuesPasswort123!',
                'password_confirmation' => 'FalschesPasswort!',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_admin_can_update_notification_preferences(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create(['teamer_notifications' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.users.notifications.update', $target), [
                'teamer_notifications' => true,
            ])
            ->assertRedirect(route('admin.users.profile', $target));

        $this->assertTrue($target->fresh()->teamer_notifications);
    }

    public function test_required_notifications_cannot_be_disabled_by_admin(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();

        // Pflichtbenachrichtigungen auch dann true, wenn Admin sie nicht sendet.
        $this->actingAs($admin)
            ->patch(route('admin.users.notifications.update', $target), []);

        $target->refresh();
        $this->assertTrue($target->notify_booking_received);
        $this->assertTrue($target->notify_booking_approved);
        $this->assertTrue($target->notify_booking_rejected);
        $this->assertTrue($target->notify_waitlist_promoted);
        $this->assertTrue($target->notify_event_cancelled);
    }

    public function test_admin_can_restore_a_soft_deleted_user(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();
        $target->delete();

        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $this->actingAs($admin)
            ->patch(route('admin.users.restore', $target->id))
            ->assertRedirect(route('admin.users.index'));

        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_non_admin_cannot_view_user_profile(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.users.profile', $target))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_update_user_profile(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.users.profile.update', $target), [
                'name'  => 'Hacker',
                'email' => 'hacker@example.com',
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_change_user_password(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.users.password.update', $target), [
                'password'              => 'NeuesPasswort123!',
                'password_confirmation' => 'NeuesPasswort123!',
            ])
            ->assertForbidden();
    }
}
