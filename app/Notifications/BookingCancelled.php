<?php

namespace App\Notifications;

use App\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Info an die Projektleitung, dass eine Anmeldung storniert wurde (ADV-21).
 */
class BookingCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Adventure $adventure,
        private readonly string $participant,
        private readonly string $cancelledByName,
        private readonly int $confirmedCount,
        private readonly int $maxPlayers,
        private readonly int $waitlistCount,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->notify_cancellation_report ?? true) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Storniert: '.$this->participant.' – '.$this->adventure->name
                .' (Stand: '.$this->confirmedCount.'/'.$this->maxPlayers
                .', Warteliste: '.$this->waitlistCount.')',
            'url' => route('adventures.manage', $this->adventure->id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Stornierung einer Anmeldung: '.$this->adventure->name)
            ->line('**'.$this->cancelledByName.'** hat die Anmeldung von **'.$this->participant.'** an der Veranstaltung „'.$this->adventure->name.'" storniert.')
            ->line('**Aktueller Stand der Anmeldungen:** '.$this->confirmedCount.'/'.$this->maxPlayers)
            ->line('**Aktuelle Spieler auf der Warteliste:** '.$this->waitlistCount)
            ->action('Zur Veranstaltungsverwaltung', route('adventures.manage', $this->adventure->id));
    }
}
