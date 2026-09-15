<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Bestätigung für Teamer-artige Buchungen nach manueller Freigabe durch den Projektleiter.
 * Keine Bankdaten – Teamer zahlen keinen Beitrag.
 */
class TeamerBookingConfirmed extends Notification implements ShouldQueue
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
            ->subject('Teamer-Anmeldung bestätigt: '.$booking->adventure?->name)
            ->markdown('emails.teamer_booking_confirmed', [
                'playerName'    => $booking->player?->full_name ?: 'Abenteurer',
                'adventureName' => $booking->adventure?->name ?? '—',
                'roleName'      => $booking->role?->description ?? 'Teamer',
                'startDate'     => optional($booking->adventure?->start_at)->format('d.m.Y'),
                'endDate'       => optional($booking->adventure?->end_at)->format('d.m.Y'),
                'dashboardUrl'  => route('dashboard'),
            ]);
    }
}
