<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Benachrichtigung, wenn ein bestätigter Teilnehmer auf die Warteliste verschoben wird.
 * Ausgelöst in BookingController@moveToWaitlist.
 */
class BookingMovedToWaitlist extends Notification implements ShouldQueue
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
            ->subject('Warteliste: '.$booking->adventure?->name)
            ->greeting('Hallo '.($booking->player?->full_name ?: '').'!')
            ->line('Deine Anmeldung für **„'.$booking->adventure?->name.'"** wurde auf die Warteliste verschoben.')
            ->line('Das bedeutet, dass dein Platz derzeit nicht gesichert ist. Sobald ein regulärer Platz frei wird, wirst du automatisch benachrichtigt.')
            ->line('Bei Fragen wende dich bitte an die Veranstalter.')
            ->action('Zum Heldenregister', route('dashboard'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking->loadMissing(['adventure']);
        return [
            'adventure_id'   => $booking->adventure?->id,
            'adventure_name' => $booking->adventure?->name,
            'message'        => 'Deine Anmeldung für „'.$booking->adventure?->name.'" wurde auf die Warteliste verschoben.',
            'url'            => route('dashboard'),
        ];
    }
}
