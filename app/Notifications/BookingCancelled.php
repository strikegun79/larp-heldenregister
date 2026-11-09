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
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Storniert: '.$this->participant.' – '.$this->adventure->name,
            'url' => route('adventures.manage-index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Anmeldung storniert: '.$this->adventure->name)
            ->line($this->participant.' wurde von „'.$this->adventure->name.'" abgemeldet/storniert.')
            ->action('Zur Event-Verwaltung', route('adventures.manage-index'));
    }
}
