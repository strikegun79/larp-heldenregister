<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Eingangsbestätigung für Teilnehmer-Anmeldungen bei Events mit „Anmeldungen bestätigen“.
 * Ohne Bankdaten – die Zahlungsaufforderung (BookingReceived) folgt erst mit der Bestätigung.
 */
class BookingPending extends Notification implements ShouldQueue
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
            ->subject('Anmeldung eingegangen – Bestätigung folgt: '.$booking->adventure?->name)
            ->markdown('emails.booking_pending', [
                'booking'      => $booking,
                'dashboardUrl' => route('dashboard'),
            ]);
    }
}
