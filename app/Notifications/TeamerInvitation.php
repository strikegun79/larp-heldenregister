<?php

namespace App\Notifications;

use App\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Teamer-Einladung zu einem Event (ADV-28).
 * Wird versendet wenn Projektleitung im Verwaltungs-Modal auf „Teamer einladen" klickt.
 */
class TeamerInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Adventure $adventure) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->teamer_notifications ?? true) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = optional($this->adventure->start_at)->format('d.m.Y');

        return (new MailMessage)
            ->subject('Teamer-Einladung: '.$this->adventure->name)
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('Du wirst als Teamer zum folgenden Event eingeladen:')
            ->line('**'.$this->adventure->name.'** am '.$date)
            ->action('Event ansehen & anmelden', route('adventures.show', $this->adventure))
            ->line('Falls du keine Teamer-Benachrichtigungen mehr erhalten möchtest, kannst du das in deinem Profil deaktivieren.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'adventure_id' => $this->adventure->id,
            'adventure_name' => $this->adventure->name,
            'start_at' => optional($this->adventure->start_at)->toDateString(),
            'message' => 'Du wurdest als Teamer zu „'.$this->adventure->name.'" eingeladen.',
            'url' => route('adventures.show', $this->adventure),
        ];
    }
}
