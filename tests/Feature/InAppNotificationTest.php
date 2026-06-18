<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * NOTI-07: In-App-Benachrichtigungen (Datenbank-Channel, Glocke, als gelesen markieren).
 */
class InAppNotificationTest extends TestCase
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

    private function notify(User $recipient): void
    {
        // Mail faken, damit nur der Datenbank-Channel real schreibt.
        Mail::fake();
        $recipient->notify(new NewUserRegistered(User::factory()->create(['name' => 'Neuling'])));
    }

    public function test_notification_is_stored_in_database(): void
    {
        $admin = $this->admin();
        $this->notify($admin);

        $this->assertCount(1, $admin->fresh()->unreadNotifications);
        $this->assertStringContainsString('Neuling', $admin->fresh()->unreadNotifications->first()->data['message']);
    }

    public function test_bell_shows_unread_count(): void
    {
        $admin = $this->admin();
        $this->notify($admin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Benachrichtigungen')
            ->assertSee('Neuling');
    }

    public function test_reading_a_notification_marks_it_read_and_redirects(): void
    {
        $admin = $this->admin();
        $this->notify($admin);
        $note = $admin->fresh()->unreadNotifications->first();

        $this->actingAs($admin)
            ->get(route('notifications.read', $note->id))
            ->assertRedirect(route('admin.users.index'));

        $this->assertCount(0, $admin->fresh()->unreadNotifications);
    }

    public function test_mark_all_as_read(): void
    {
        $admin = $this->admin();
        $this->notify($admin);
        $this->notify($admin);

        $this->actingAs($admin)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertCount(0, $admin->fresh()->unreadNotifications);
    }

    public function test_cannot_read_another_users_notification(): void
    {
        $admin = $this->admin();
        $this->notify($admin);
        $note = $admin->fresh()->unreadNotifications->first();

        $other = $this->admin();
        $this->actingAs($other)
            ->get(route('notifications.read', $note->id))
            ->assertNotFound();
    }
}
