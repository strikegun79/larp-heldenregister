<?php

namespace App\Notifications;

use App\Models\Player;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Issue #31: Talk-Freigabe zurückgenommen (Bürokrat) bzw. Erlaubnis widerrufen (Eltern).
 * Geht an die Nutzer-E-Mail.
 */
class TalkAccessEnded extends Notification implements ShouldQueue
{
    use Queueable;

    public const REVOKED = 'revoked';

    public const WITHDRAWN = 'withdrawn';

    public function __construct(private readonly Player $player, private readonly string $reason) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $what = $this->reason === self::WITHDRAWN
            ? 'Die Erlaubnis für Nextcloud Talk wurde widerrufen'
            : 'Die Freigabe für Nextcloud Talk wurde vom Verein zurückgenommen';

        return (new MailMessage)
            ->subject('Nextcloud Talk beendet: '.$this->player->full_name)
            ->greeting('Hallo '.$notifiable->name.',')
            ->line($what.' – betroffen ist **'.$this->player->full_name.'**.')
            ->line('Ein vom Heldenregister angelegtes Talk-Konto wird deaktiviert. Bei Fragen meldet euch gerne beim Organisationsteam.')
            ->action('Zum Spieler', route('players.show', $this->player));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'player_id' => $this->player->id,
            'message' => ($this->reason === self::WITHDRAWN ? 'Talk-Erlaubnis widerrufen' : 'Talk-Freigabe zurückgenommen')
                .' für '.$this->player->full_name.'.',
            'url' => route('players.show', $this->player),
        ];
    }
}
