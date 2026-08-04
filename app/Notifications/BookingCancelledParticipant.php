<?php

namespace App\Notifications;

use App\Models\Adventure;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOTI-10: Stornierungsbestätigung an den Teilnehmer selbst.
 * Ausgelöst in BookingController@destroy; ergänzt BookingCancelled (→ Projektleiter).
 */
class BookingCancelledParticipant extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Adventure $adventure) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = $notifiable instanceof User ? ['database'] : [];
        $channels[] = 'mail';
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = optional($this->adventure->start_at)->format('d.m.Y');

        return (new MailMessage)
            ->subject('Abmeldung bestätigt: '.$this->adventure->name)
            ->line('Deine Anmeldung für „'.$this->adventure->name.'" ('.$date.') wurde storniert.')
            ->line('Falls dies ein Versehen war, melde dich bitte bei den Veranstaltern.')
            ->action('Zum Heldenregister', route('dashboard'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'adventure_id'   => $this->adventure->id,
            'adventure_name' => $this->adventure->name,
            'message'        => 'Deine Anmeldung für „'.$this->adventure->name.'" wurde storniert.',
            'url'            => route('dashboard'),
        ];
    }
}
