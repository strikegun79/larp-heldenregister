<?php

namespace App\Notifications;

use App\Models\Player;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Issue #31: Das Nextcloud-Konto eines Spielers ist eingerichtet. Geht an die Nutzer-E-Mail.
 */
class TalkAccountReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Player $player, private readonly ?string $accountEmail) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Talk-Konto eingerichtet: '.$this->player->full_name)
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('Das Nextcloud-Talk-Konto für **'.$this->player->full_name.'** ist eingerichtet.')
            ->line('Benutzername: **'.$this->player->nextcloud_user_id.'**');

        if ($this->player->nextcloud_account_created && $this->accountEmail) {
            $mail->line('Nextcloud hat eine Willkommens-E-Mail mit dem Link zum Setzen des Passworts an '.$this->accountEmail.' geschickt.');
        } else {
            $mail->line('Es wird das bereits vorhandene Nextcloud-Konto genutzt.');
        }

        if ($appUrl = Setting::get('talk_app_url')) {
            $mail->action('Zur Talk-App', $appUrl);
        }

        return $mail->line('Hilfe zu Talk: '.(Setting::get('talk_help_url') ?: config('nextcloud.default_help_url')));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'player_id' => $this->player->id,
            'message' => 'Talk-Konto für '.$this->player->full_name.' eingerichtet (Benutzername: '.$this->player->nextcloud_user_id.').',
            'url' => route('players.show', $this->player),
        ];
    }
}
