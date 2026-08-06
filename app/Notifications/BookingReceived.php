<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Bestätigung an den Spieler/Betreuer, dass die Event-Anmeldung eingegangen ist
 * (NOTI-02). Ausgelöst in BookingController@store.
 */
class BookingReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Booking $booking) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing(['adventure.client', 'player', 'role']);

        $mail = (new MailMessage)
            ->subject('Anmeldung eingegangen: '.$booking->adventure?->name)
            ->greeting('Hallo '.($booking->player?->full_name ?: '').'!')
            ->line('Deine Anmeldung für „'.$booking->adventure?->name.'" ist eingegangen.')
            ->line('Rolle: '.($booking->role?->description ?? '—'));

        if ($booking->waitlisted) {
            $mail->line('Hinweis: Das Abenteuer ist derzeit voll – du stehst auf der Warteliste und rückst bei einem frei werdenden Platz automatisch nach.');
        }

        $fee = $booking->effectiveFee();
        if ($fee > 0) {
            $mail->line('---');
            $feeLabel = number_format($fee, 2, ',', '.') . ' €';
            if ($booking->ermaessigung) {
                $feeLabel .= ' *(ermäßigt – Nachweis beim Check-in erforderlich)*';
            }
            $mail->line('**Zu zahlender Beitrag:** ' . $feeLabel);

            $iban  = Setting::get('bank_iban');
            $owner = Setting::get('bank_account_owner');
            $bank  = Setting::get('bank_name');

            if ($iban) {
                $kuerzel = $booking->adventure?->client?->kuerzel;
                $date    = optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—';
                $player  = $booking->player?->full_name ?? '';
                $verwendungszweck = trim(($kuerzel ? $kuerzel.' ' : '').$date.' '.$player);

                $mail->line('**Bankverbindung:**');
                if ($owner) $mail->line('Kontoinhaber: ' . $owner);
                $mail->line('IBAN: ' . $iban);
                if ($bank) $mail->line('Bank: ' . $bank);
                $mail->line('Verwendungszweck: "'.$verwendungszweck.'"');
            }
        }

        return $mail->action('Zum Heldenregister', route('dashboard'));
    }
}
