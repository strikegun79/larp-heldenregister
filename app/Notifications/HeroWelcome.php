<?php

namespace App\Notifications;

use App\Models\Hero;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Willkommens-Mail an Eltern/Betreuer, wenn ein Held im Heldenregister eingetragen wird.
 * Ausgelöst manuell durch Bürokrat/Admin in der Helden-Verwaltungsansicht.
 */
class HeroWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Hero $hero) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hero       = $this->hero;
        $hero->loadMissing('player');
        // Kein Heldenname → Realname des Spielers als Fallback (Kinder finden oft keinen Namen).
        $heroName   = $hero->character_name ?: ($hero->player?->full_name ?? 'Dein Held');
        $publicUrl  = $hero->public_code ? route('public.hero', $hero->public_code) : null;
        $searchUrl  = url('/h/');

        return (new MailMessage)
            ->subject("Willkommen im Heldenregister, {$heroName}!")
            ->markdown('emails.hero_welcome', [
                'hero'      => $hero,
                'heroName'  => $heroName,
                'publicUrl' => $publicUrl,
                'searchUrl' => $searchUrl,
            ]);
    }
}
