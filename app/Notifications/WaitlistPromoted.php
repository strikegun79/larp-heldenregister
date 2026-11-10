<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Benachrichtigung an einen Spieler, der von der Warteliste nachgerückt ist
 * (NOTI-03). Ausgelöst in BookingController@destroy (automatisch) oder
 * resendConfirmation (manuell durch Admin).
 * Enthält Bankdaten + QR-Code, da der Platz jetzt regulär bestätigt ist.
 */
class WaitlistPromoted extends Notification implements ShouldQueue
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
        $booking = $this->booking->loadMissing(['adventure', 'player', 'role']);

        $fee             = $booking->effectiveFee();
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
                $date    = optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—';
                $player  = $booking->player?->full_name ?? '';
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
            ->subject('Nachgerückt: '.$booking->adventure?->name)
            ->markdown('emails.waitlist_promoted', [
                'booking'          => $booking,
                'fee'              => $fee,
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
}
