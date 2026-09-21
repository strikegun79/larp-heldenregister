<?php

namespace App\Notifications;

use App\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Benachrichtigung an den Nutzer über das Ergebnis seiner Rücknahme-Anfrage.
 */
class BookingReinstateResult extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Adventure $adventure,
        private readonly string $participantName,
        private readonly bool $approved,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->approved) {
            return (new MailMessage)
                ->subject('Stornierung rückgängig gemacht: '.$this->adventure->name)
                ->line('Deine Anfrage zur Rücknahme der Stornierung von **'.$this->participantName.'** für die Veranstaltung **„'.$this->adventure->name.'"** wurde **genehmigt**.')
                ->line('Die Anmeldung ist wieder aktiv.')
                ->action('Zum Heldenregister', route('dashboard'));
        }

        return (new MailMessage)
            ->subject('Anfrage abgelehnt: '.$this->adventure->name)
            ->line('Deine Anfrage zur Rücknahme der Stornierung von **'.$this->participantName.'** für die Veranstaltung **„'.$this->adventure->name.'"** wurde **abgelehnt**.')
            ->line('Bei Fragen wende dich bitte direkt an die Veranstalter.')
            ->action('Zum Heldenregister', route('dashboard'));
    }
}
