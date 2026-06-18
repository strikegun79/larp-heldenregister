<?php

namespace App\Notifications;

use App\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Erinnerung an bestätigte Teilnehmer vor Eventbeginn (NOTI-05).
 * Ausgelöst vom Scheduled Command `events:send-reminders`.
 */
class EventReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Adventure $adventure) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->adventure->start_at;

        return (new MailMessage)
            ->subject('Erinnerung: '.$this->adventure->name)
            ->greeting('Bald geht es los!')
            ->line('Dein Abenteuer „'.$this->adventure->name.'" findet am '.optional($start)->format('d.m.Y \u\m H:i').' Uhr statt.')
            ->line('Ort: '.($this->adventure->location?->titel ?? 'wird noch bekannt gegeben'))
            ->action('Zum Event', route('adventures.index'))
            ->line('Wir freuen uns auf dich!');
    }
}
