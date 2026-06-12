<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Notifications\EventReminder;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * NOTI-05 / INFRA-05: Scheduled Command sendet Event-Erinnerungen an bestätigte
 * Teilnehmer; idempotent.
 */
class EventReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function confirmedBookingFor(Adventure $adventure, ?string $email): void
    {
        Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create(['email' => $email])->id,
            'status' => 'bestaetigt',
        ]);
    }

    public function test_reminder_is_sent_for_upcoming_event(): void
    {
        Notification::fake();
        $event = Adventure::factory()->create(['start_at' => now()->addDays(2), 'end_at' => now()->addDays(2)->addHours(6)]);
        $this->confirmedBookingFor($event, 'teilnehmer@example.test');

        $this->artisan('events:send-reminders')->assertSuccessful();

        Notification::assertSentOnDemand(
            EventReminder::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'teilnehmer@example.test'
        );
        $this->assertNotNull($event->fresh()->reminder_sent_at);
    }

    public function test_reminder_is_idempotent(): void
    {
        Notification::fake();
        $event = Adventure::factory()->create(['start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHours(6)]);
        $this->confirmedBookingFor($event, 'a@example.test');

        $this->artisan('events:send-reminders')->assertSuccessful();
        $this->artisan('events:send-reminders')->assertSuccessful();

        Notification::assertSentOnDemandTimes(EventReminder::class, 1);
    }

    public function test_far_future_event_is_not_reminded(): void
    {
        Notification::fake();
        $event = Adventure::factory()->create(['start_at' => now()->addDays(30), 'end_at' => now()->addDays(30)->addHours(6)]);
        $this->confirmedBookingFor($event, 'a@example.test');

        $this->artisan('events:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($event->fresh()->reminder_sent_at);
    }

    public function test_cancelled_event_is_not_reminded(): void
    {
        Notification::fake();
        $event = Adventure::factory()->create([
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHours(6),
            'event_status_id' => 70, // abgesagt
        ]);
        $this->confirmedBookingFor($event, 'a@example.test');

        $this->artisan('events:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_unconfirmed_booking_is_not_reminded(): void
    {
        Notification::fake();
        $event = Adventure::factory()->create(['start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHours(6)]);
        Booking::factory()->for($event)->create([
            'player_id' => Player::factory()->create(['email' => 'offen@example.test'])->id,
            'status' => 'offen',
        ]);

        $this->artisan('events:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
