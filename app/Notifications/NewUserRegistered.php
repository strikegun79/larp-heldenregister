<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Benachrichtigung an die Admins, wenn sich ein neuer Benutzer registriert.
 * Ersetzt die Legacy-Smarty-Vorlage email_admin_new-user.tpl.
 */
class NewUserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $newUser) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * In-App-Benachrichtigung (NOTI-07): Payload für die Glocke im Header.
     *
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Neuer Benutzer: '.trim("{$this->newUser->name} {$this->newUser->lastname}"),
            'url' => route('admin.users.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = trim("{$this->newUser->name} {$this->newUser->lastname}");

        return (new MailMessage)
            ->subject('Neuer Benutzer am Heldenregister')
            ->greeting('Neuer Benutzer hat im Heldenregister registriert.')
            ->line('Vorname: '.($this->newUser->name ?: '—'))
            ->line('Nachname: '.($this->newUser->lastname ?: '—'))
            ->line('E-Mail: '.$this->newUser->email)
            ->line('Handy: '.($this->newUser->phone ?: '—'))
            ->action('Zum Portal', route('dashboard'))
            ->line("Bitte weise {$name} dort einer Rolle zu.");
    }
}
