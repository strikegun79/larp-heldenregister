<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Benachrichtigung an den Spieler/Betreuer, dass die Anmeldung eingegangen ist,
 * aber auf der Warteliste steht (NOTI-02b).
 * Kein Beitrag/Bankdaten – erst nach Nachrücken via BookingReceived.
 */
class BookingWaitlisted extends Notification implements ShouldQueue
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
            ->subject('Warteliste: '.$booking->adventure?->name)
            ->markdown('emails.booking_waitlisted', [
                'booking'      => $booking,
                'dashboardUrl' => route('dashboard'),
            ]);
    }
}
