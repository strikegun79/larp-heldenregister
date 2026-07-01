<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * AUTH-13: E-Mail-Benachrichtigungen per Rolle im Profil konfigurierbar.
 */
class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_erhaelt_email_wenn_notify_new_user_aktiv(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['notify_new_user' => true]);
        $admin->roles()->attach(10);

        $newUser = User::factory()->create();
        $admin->notify(new NewUserRegistered($newUser));

        Notification::assertSentTo($admin, NewUserRegistered::class, function ($n) use ($admin) {
            return in_array('mail', $n->via($admin));
        });
    }

    public function test_admin_erhaelt_keine_email_wenn_notify_new_user_deaktiviert(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['notify_new_user' => false]);
        $admin->roles()->attach(10);

        $newUser = User::factory()->create();
        $admin->notify(new NewUserRegistered($newUser));

        Notification::assertSentTo($admin, NewUserRegistered::class, function ($n) use ($admin) {
            return ! in_array('mail', $n->via($admin));
        });
    }

    public function test_portal_benachrichtigung_bleibt_aktiv_wenn_email_deaktiviert(): void
    {
        $admin = User::factory()->create(['notify_new_user' => false]);

        $newUser = User::factory()->create();
        $notification = new NewUserRegistered($newUser);

        $channels = $notification->via($admin);
        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels);
    }

    public function test_profil_speichert_notify_new_user(): void
    {
        $admin = User::factory()->create(['notify_new_user' => true]);
        $admin->roles()->attach(10);

        $this->actingAs($admin)->patch(route('profile.update'), [
            'name'           => $admin->name,
            'lastname'       => $admin->lastname,
            'email'          => $admin->email,
            'phone'          => $admin->phone ?? '0000',
            'notify_new_user' => '0',
        ]);

        $this->assertFalse($admin->fresh()->notify_new_user);
    }

    public function test_profil_speichert_teamer_notifications(): void
    {
        $teamer = User::factory()->create(['teamer_notifications' => true]);
        $teamer->roles()->attach(30); // Teamer

        $this->actingAs($teamer)->patch(route('profile.update'), [
            'name'                 => $teamer->name,
            'lastname'             => $teamer->lastname,
            'email'                => $teamer->email,
            'phone'                => $teamer->phone ?? '0000',
            'teamer_notifications' => '0',
        ]);

        $this->assertFalse($teamer->fresh()->teamer_notifications);
    }
}
