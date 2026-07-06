<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
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
        $channels = $notifiable instanceof User ? ['database'] : [];
        $channels[] = 'mail';
        return $channels;
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

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking->loadMissing(['adventure']);
        return [
            'adventure_id'   => $booking->adventure?->id,
            'adventure_name' => $booking->adventure?->name,
            'message'        => 'Deine Anmeldung für „'.$booking->adventure?->name.'" wurde abgelehnt.',
            'url'            => route('dashboard'),
        ];
    }
}
