<?php

namespace App\Notifications;

use App\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Teamer-Anmeldung bestätigt – Portal + Mail (wenn teamer_notifications aktiv). */
class TeamerApproved extends Notification implements ShouldQueue
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
            ->subject('Teamer-Anmeldung bestätigt: '.$this->adventure->name)
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('Deine Teamer-Anmeldung für „'.$this->adventure->name.'" wurde bestätigt.')
            ->line('Datum: '.$date)
            ->action('Event ansehen', route('adventures.show', $this->adventure));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'adventure_id'   => $this->adventure->id,
            'adventure_name' => $this->adventure->name,
            'start_at'       => optional($this->adventure->start_at)->toDateString(),
            'message'        => 'Deine Teamer-Anmeldung für „'.$this->adventure->name.'" wurde bestätigt.',
            'url'            => route('adventures.show', $this->adventure),
        ];
    }
}
