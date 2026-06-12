<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\User;
use App\Notifications\BookingReceived;
use App\Notifications\EventCancelled;
use App\Notifications\WaitlistPromoted;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * NOTI-02/03/04: Mails bei Anmeldung, Wartelisten-Nachrücken und Event-Absage.
 */
class BookingNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function bookerWith(Player $player): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(60); // Event buchen
        $user->players()->attach($player->id, ['self' => true]);

        return $user;
    }

    public function test_booking_sends_confirmation_to_player(): void
    {
        Notification::fake();
        $player = Player::factory()->create(['email' => 'spieler@example.test']);
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWith($player))
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])->assertOk();

        Notification::assertSentOnDemand(
            BookingReceived::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'spieler@example.test'
        );
    }

    public function test_no_confirmation_when_player_has_no_email(): void
    {
        Notification::fake();
        $player = Player::factory()->create(['email' => null]);
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->bookerWith($player))
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_promoted_player_is_notified(): void
    {
        Notification::fake();
        $adventure = Adventure::factory()->create(['max_player' => 1]);
        $regular = Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'waitlisted' => false,
        ]);
        $waiting = Player::factory()->create(['email' => 'warteliste@example.test']);
        Booking::factory()->for($adventure)->create(['player_id' => $waiting->id, 'waitlisted' => true]);

        $registrar = User::factory()->create();
        $registrar->roles()->attach(20);

        $this->actingAs($registrar)
            ->deleteJson(route('adventures.bookings.destroy', [$adventure, $regular]))
            ->assertOk();

        Notification::assertSentOnDemand(
            WaitlistPromoted::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'warteliste@example.test'
        );
    }

    public function test_cancelling_event_notifies_all_booked_players(): void
    {
        Notification::fake();
        $adventure = Adventure::factory()->create();
        $a = Player::factory()->create(['email' => 'a@example.test']);
        $b = Player::factory()->create(['email' => 'b@example.test']);
        $noMail = Player::factory()->create(['email' => null]);
        foreach ([$a, $b, $noMail] as $p) {
            Booking::factory()->for($adventure)->create(['player_id' => $p->id]);
        }

        $registrar = User::factory()->create();
        $registrar->roles()->attach(20);

        $this->actingAs($registrar)
            ->patchJson(route('adventures.cancel', $adventure))
            ->assertOk();

        Notification::assertSentOnDemandTimes(EventCancelled::class, 2); // nur die mit E-Mail
    }
}
