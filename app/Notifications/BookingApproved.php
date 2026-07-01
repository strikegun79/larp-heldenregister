<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOTI-10: Buchung offiziell bestätigt – an den Spieler/Betreuer.
 * Ausgelöst in BookingController@approve wenn approved_at gesetzt wird.
 */
class BookingApproved extends Notification implements ShouldQueue
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
            ->subject('Anmeldung bestätigt: '.$booking->adventure?->name)
            ->greeting('Hallo '.($booking->player?->full_name ?: '').'!')
            ->line('Deine Anmeldung für „'.$booking->adventure?->name.'" wurde offiziell bestätigt.')
            ->line('Rolle: '.($booking->role?->description ?? '—'))
            ->line('Datum: '.(optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—'))
            ->action('Zum Heldenportal', route('dashboard'));
    }
}
