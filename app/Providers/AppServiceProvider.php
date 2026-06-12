<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Deutsche, gebrandete Auth-Mails (NOTI-06). Layout (Logo/Footer) kommt
        // aus den angepassten Mail-Komponenten (resources/views/vendor/mail).
        VerifyEmail::toMailUsing(function ($notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Bestätige deine E-Mail-Adresse')
                ->greeting('Hallo!')
                ->line('Bitte bestätige deine E-Mail-Adresse, um dein Konto im Heldenregister zu aktivieren.')
                ->action('E-Mail-Adresse bestätigen', $url)
                ->line('Falls du dich nicht registriert hast, ist keine weitere Aktion nötig.')
                ->salutation("Viele Grüße\nDein Heldenregister-Team");
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token): MailMessage {
            $url = route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject('Passwort zurücksetzen')
                ->greeting('Hallo!')
                ->line('Du erhältst diese E-Mail, weil für dein Konto ein Zurücksetzen des Passworts angefordert wurde.')
                ->action('Passwort zurücksetzen', $url)
                ->line("Dieser Link ist {$minutes} Minuten gültig.")
                ->line('Falls du das nicht angefordert hast, ist keine weitere Aktion nötig.')
                ->salutation("Viele Grüße\nDein Heldenregister-Team");
        });
    }
}
