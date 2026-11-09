<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Bestätigung an den Spieler/Betreuer, dass die Event-Anmeldung eingegangen ist
 * (NOTI-02). Ausgelöst in BookingController@store.
 */
class BookingReceived extends Notification implements ShouldQueue
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

        $mail = (new MailMessage)
            ->subject('Anmeldung eingegangen: '.$booking->adventure?->name)
            ->greeting('Hallo '.($booking->player?->full_name ?: '').'!')
            ->line('Deine Anmeldung für „'.$booking->adventure?->name.'" ist eingegangen.')
            ->line('Rolle: '.($booking->role?->description ?? '—'));

        if ($booking->waitlisted) {
            $mail->line('Hinweis: Das Abenteuer ist derzeit voll – du stehst auf der Warteliste und rückst bei einem frei werdenden Platz automatisch nach.');
        }

        return $mail->action('Zum Heldenportal', route('dashboard'));
    }
}
