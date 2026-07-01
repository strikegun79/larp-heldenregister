<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOTI-10: Zahlungseingang bestätigt – an den Spieler/Betreuer.
 * Ausgelöst in BookingController@togglePaid wenn paid auf true gesetzt wird.
 */
class PaymentConfirmed extends Notification implements ShouldQueue
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
            ->subject('Zahlung eingegangen: '.$booking->adventure?->name)
            ->greeting('Hallo '.($booking->player?->full_name ?: '').'!')
            ->line('Deine Zahlung für „'.$booking->adventure?->name.'" ist bei uns eingegangen.')
            ->line('Datum: '.(optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—'))
            ->action('Zum Heldenportal', route('dashboard'));
    }
}
