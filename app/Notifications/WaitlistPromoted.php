<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Benachrichtigung an einen Spieler, der von der Warteliste nachgerückt ist
 * (NOTI-03). Ausgelöst in BookingController@destroy.
 */
class WaitlistPromoted extends Notification implements ShouldQueue
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
            ->subject('Nachgerückt: '.$booking->adventure?->name)
            ->greeting('Gute Nachricht, '.($booking->player?->full_name ?: '').'!')
            ->line('Für „'.$booking->adventure?->name.'" ist ein Platz frei geworden – du bist von der Warteliste nachgerückt und nimmst regulär teil.')
            ->action('Zum Heldenportal', route('dashboard'));
    }
}
