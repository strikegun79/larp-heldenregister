<?php

namespace App\Notifications;

use App\Models\Adventure;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Benachrichtigung an Projektleitung und Portal-Kontakt, wenn eine
 * Fotoerlaubnis widerrufen wird (N-2 / Art. 7 DSGVO).
 */
class FotoerlaubnisRevoked extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly Adventure $adventure,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $participant = $this->booking->participant_name;
        $event       = $this->adventure->name;

        return (new MailMessage)
            ->subject("Fotoerlaubnis widerrufen: {$participant} – {$event}")
            ->line("Die Fotoerlaubnis für **{$participant}** bei \"{$event}\" wurde widerrufen.")
            ->line('Der Widerruf gilt ab sofort für zukünftige Aufnahmen.')
            ->line(
                'Bitte stellt sicher, dass der Teilnehmer auf zukünftigen Fotos nicht '.
                'mehr erscheint und bereits veröffentlichte Aufnahmen auf Wunsch entfernt werden.'
            )
            ->action('Zur Event-Verwaltung', route('adventures.manage-index'));
    }
}
