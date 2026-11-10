<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

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
        $booking = $this->booking->loadMissing(['adventure', 'player', 'role']);

        $fee     = $booking->effectiveFee();
        $date    = optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—';
        $player  = $booking->player?->full_name ?? '';

        $bankData        = null;
        $verwendungszweck = null;
        $qrCid           = null;
        $qrPart          = null;

        if ($fee > 0) {
            $iban  = Setting::get('bank_iban');
            $owner = Setting::get('bank_account_owner');
            $bic   = Setting::get('bank_bic');
            $bank  = Setting::get('bank_name');

            if ($iban) {
                $kuerzel = $booking->adventure?->kuerzel;
                $verwendungszweck = trim(($kuerzel ? $kuerzel.' ' : '').$date.' '.$player);
                $bankData = compact('iban', 'owner', 'bic', 'bank');

                if ($bic && $owner) {
                    $qrPng = BookingReceived::buildEpcQrPng($bic, $owner, $iban, $fee, $verwendungszweck);
                    if ($qrPng) {
                        $qrPart = (new DataPart($qrPng, 'girocode.png', 'image/png'))->asInline();
                        $qrCid  = 'cid:'.$qrPart->getContentId();
                    }
                }
            }
        }

        $mail = (new MailMessage)
            ->subject('Anmeldung bestätigt: '.$booking->adventure?->name)
            ->markdown('emails.booking_approved', [
                'booking'          => $booking,
                'fee'              => $fee,
                'date'             => $date,
                'bankData'         => $bankData,
                'verwendungszweck' => $verwendungszweck,
                'qrCid'            => $qrCid,
                'dashboardUrl'     => route('dashboard'),
            ]);

        if ($qrPart) {
            $mail->withSymfonyMessage(static function (Email $email) use ($qrPart): void {
                $email->addPart($qrPart);
            });
        }

        return $mail;
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
