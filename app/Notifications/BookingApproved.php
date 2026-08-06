<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NOTI-10: Buchung offiziell bestätigt – an den Spieler/Betreuer.
 * Ausgelöst in BookingController@approve wenn approved_at gesetzt wird.
 */
class BookingApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Booking $booking) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = $notifiable instanceof User ? ['database'] : [];
        $channels[] = 'mail';
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->loadMissing(['adventure.client', 'player', 'role']);

        $fee    = $booking->effectiveFee();
        $date   = optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—';
        $player = $booking->player?->full_name ?? '';

        $mail = (new MailMessage)
            ->subject('Anmeldung bestätigt: '.$booking->adventure?->name)
            ->greeting('Hallo '.$player.'!')
            ->line('Deine Anmeldung für „'.$booking->adventure?->name.'" wurde bestätigt.')
            ->line('Rolle: '.($booking->role?->description ?? '—'))
            ->line('Datum: '.$date);

        if ($fee > 0) {
            $iban  = Setting::get('bank_iban');
            $owner = Setting::get('bank_account_owner');
            $bic   = Setting::get('bank_bic');
            $bank  = Setting::get('bank_name');

            $feeText = number_format($fee, 2, ',', '.').' €';
            if ($booking->ermaessigung) {
                $feeText .= ' *(ermäßigt – Nachweis beim Check-in erforderlich)*';
            }

            $mail->line('');
            $mail->line('Bitte überweise Deinen Teilnahmebeitrag von **'.$feeText.'** für die Anmeldung auf das folgende Konto:');

            if ($owner) $mail->line('Empfänger: '.$owner);
            if ($iban)  $mail->line('IBAN: '.$iban);
            if ($bic)   $mail->line('BIC: '.$bic);
            if ($bank)  $mail->line($bank);

            $kuerzel = $booking->adventure?->client?->kuerzel;
            $verwendungszweck = trim(($kuerzel ? $kuerzel.' ' : '').$date.' '.$player);
            $mail->line('Verwendungszweck: "'.$verwendungszweck.'"');
        }

        $mail->line('');
        $mail->line('Am Dienstag vor der Veranstaltung bekommst Du eine E-Mail mit allen Informationen und Spielortbeschreibung.');

        return $mail->action('Zum Heldenregister', route('dashboard'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking->loadMissing(['adventure']);
        return [
            'adventure_id'   => $booking->adventure?->id,
            'adventure_name' => $booking->adventure?->name,
            'message'        => 'Deine Anmeldung für „'.$booking->adventure?->name.'" wurde bestätigt.',
            'url'            => route('dashboard'),
        ];
    }
}
