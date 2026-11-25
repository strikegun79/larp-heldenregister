<?php

namespace App\Notifications;

use App\Models\Player;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Issue #31: Der Bürokrat hat Nextcloud Talk für einen Spieler freigegeben.
 * Geht an die Nutzer-E-Mail (Eltern bzw. Spieler selbst) – immer, da es um eine Einwilligung geht.
 */
class TalkAccessApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Player $player) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Nextcloud Talk freigegeben: '.$this->player->full_name)
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('Nextcloud Talk wurde für **'.$this->player->full_name.'** freigegeben.');

        if ($this->player->isTalkMinor()) {
            return $mail
                ->line('Bevor das Talk-Konto eingerichtet wird, braucht es eure Erlaubnis. Bitte lest im Reiter „Talk“ die Teilnahmebedingungen, Sicherheitshinweise und Verhaltensregeln und erteilt dort die Erlaubnis.')
                ->action('Zum Reiter „Talk“', route('players.show', $this->player));
        }

        return $mail
            ->line('Das Talk-Konto wird jetzt eingerichtet. Sobald es fertig ist, bekommst du eine weitere Nachricht.')
            ->action('Zum Spieler', route('players.show', $this->player));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'player_id' => $this->player->id,
            'message' => $this->player->isTalkMinor()
                ? 'Talk für '.$this->player->full_name.' freigegeben – bitte im Reiter „Talk“ die Erlaubnis erteilen.'
                : 'Talk für '.$this->player->full_name.' freigegeben – das Konto wird eingerichtet.',
            'url' => route('players.show', $this->player),
        ];
    }
}
