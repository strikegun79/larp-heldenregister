<?php

namespace App\Notifications;

use App\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Benachrichtigung an alle gebuchten Spieler, dass ein Event abgesagt wurde
 * (NOTI-04). Ausgelöst in AdventureController@cancel.
 */
class EventCancelled extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Abgesagt: '.$this->adventure->name)
            ->greeting('Leider müssen wir absagen.')
            ->line('Das Abenteuer „'.$this->adventure->name.'" am '.optional($this->adventure->start_at)->format('d.m.Y').' wurde abgesagt.')
            ->line('Deine Anmeldung ist damit hinfällig – du musst nichts weiter tun.')
            ->action('Weitere Events ansehen', route('adventures.index'));
    }
}
