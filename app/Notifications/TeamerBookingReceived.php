<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Eingangsbestätigung für Teamer-artige Buchungen (for_teamer=true oder NSC-Elternteil).
 * Kein Bankdaten-Block – die Anmeldung wartet auf manuelle Bestätigung durch den Projektleiter.
 */
class TeamerBookingReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Booking $booking) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing(['adventure', 'player', 'role']);

        return (new MailMessage)
            ->subject('Teamer-Anmeldung eingegangen: '.$booking->adventure?->name)
            ->markdown('emails.teamer_booking_received', [
                'booking'      => $booking,
                'dashboardUrl' => route('dashboard'),
            ]);
    }
}
