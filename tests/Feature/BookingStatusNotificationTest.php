<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\Player;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelledParticipant;
use App\Notifications\BookingRejected;
use App\Notifications\PaymentConfirmed;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * NOTI-10: E-Mail-Benachrichtigungen bei Buchungsbestätigung, Ablehnung,
 * Stornierung (Teilnehmer) und Zahlungseingang.
 */
class BookingStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat

        return $user;
    }

    private function bookingWithEmail(): array
    {
        $player    = Player::factory()->create(['email' => 'teilnehmer@example.test']);
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $booking   = Booking::factory()->create([
            'adventure_id' => $adventure->id,
            'player_id'    => $player->id,
            'status'       => 'offen',
            'approved_at'  => null,
            'paid'         => false,
            'waitlisted'   => false,
        ]);

        return [$adventure, $booking, $player];
    }

    public function test_buchungsbestaetigung_wird_an_spieler_gesendet(): void
    {
        Notification::fake();
        [$adventure, $booking] = $this->bookingWithEmail();

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.bookings.approval', [$adventure, $booking]));

        Notification::assertSentOnDemand(BookingApproved::class);
    }

    public function test_keine_mail_beim_zuruecknehmen_der_bestaetigung(): void
    {
        Notification::fake();
        [$adventure, $booking] = $this->bookingWithEmail();
        $booking->update(['approved_at' => now(), 'status' => 'bestaetigt']);

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.bookings.approval', [$adventure, $booking]));

        Notification::assertNothingSent();
    }

    public function test_ablehnung_wird_an_spieler_gesendet(): void
    {
        Notification::fake();
        [$adventure, $booking] = $this->bookingWithEmail();

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.bookings.rejection', [$adventure, $booking]));

        Notification::assertSentOnDemand(BookingRejected::class);
    }

    public function test_keine_mail_beim_zuruecknehmen_der_ablehnung(): void
    {
        Notification::fake();
        [$adventure, $booking] = $this->bookingWithEmail();
        $booking->update(['status' => 'abgelehnt']);

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.bookings.rejection', [$adventure, $booking]));

        Notification::assertNothingSent();
    }

    public function test_stornierung_wird_an_teilnehmer_gesendet(): void
    {
        Notification::fake();
        [$adventure, $booking] = $this->bookingWithEmail();

        $this->actingAs($this->registrar())
            ->deleteJson(route('adventures.bookings.destroy', [$adventure, $booking]));

        Notification::assertSentOnDemand(BookingCancelledParticipant::class);
    }

    public function test_zahlungsbestaetigung_wird_gesendet_wenn_als_bezahlt_markiert(): void
    {
        Notification::fake();
        [$adventure, $booking] = $this->bookingWithEmail();

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.bookings.payment', [$adventure, $booking]));

        Notification::assertSentOnDemand(PaymentConfirmed::class);
    }

    public function test_keine_zahlungsmail_beim_zuruecknehmen_der_zahlung(): void
    {
        Notification::fake();
        [$adventure, $booking] = $this->bookingWithEmail();
        $booking->update(['paid' => true]);

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.bookings.payment', [$adventure, $booking]));

        Notification::assertNothingSent();
    }

    public function test_keine_mail_wenn_spieler_keine_email_hat(): void
    {
        Notification::fake();
        $player    = Player::factory()->create(['email' => null]);
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $booking   = Booking::factory()->create([
            'adventure_id' => $adventure->id,
            'player_id'    => $player->id,
            'status'       => 'offen',
        ]);

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.bookings.approval', [$adventure, $booking]));

        Notification::assertNothingSent();
    }
}
