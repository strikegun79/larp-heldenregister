<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOTI-10: Buchung abgelehnt – an den Spieler/Betreuer.
 * Ausgelöst in BookingController@reject wenn status auf 'abgelehnt' gesetzt wird.
 */
class BookingRejected extends Notification implements ShouldQueue
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
        $booking = $this->booking->loadMissing(['adventure', 'player']);

        return (new MailMessage)
            ->subject('Anmeldung abgelehnt: '.$booking->adventure?->name)
            ->greeting('Hallo '.($booking->player?->full_name ?: '').'!')
            ->line('Deine Anmeldung für „'.$booking->adventure?->name.'" wurde leider abgelehnt.')
            ->line('Bei Fragen wende dich bitte an die Veranstalter.')
            ->action('Zum Heldenportal', route('dashboard'));
    }
}
