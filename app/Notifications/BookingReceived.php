<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Setting;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

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
                    $qrPng = self::buildEpcQrPng($bic, $owner, $iban, $fee, $verwendungszweck);
                    if ($qrPng) {
                        // CID vor dem Template-Render bestimmen; Part via withSymfonyMessage anhängen.
                        $qrPart = (new DataPart($qrPng, 'girocode.png', 'image/png'))->asInline();
                        $qrCid  = 'cid:'.$qrPart->getContentId();
                    }
                }
            }
        }

        $mail = (new MailMessage)
            ->subject('Anmeldung eingegangen: '.$booking->adventure?->name)
            ->markdown('emails.booking_received', [
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

    /**
     * Erzeugt einen EPC-QR-Code (GiroCode, ISO 20022) als rohe PNG-Bytes.
     * Gibt null zurück wenn die Generierung fehlschlägt.
     */
    public static function buildEpcQrPng(
        string $bic,
        string $owner,
        string $iban,
        float  $amount,
        string $verwendungszweck,
    ): ?string {
        $epcContent = implode("\n", [
            'BCD',
            '002',
            '1',
            'SCT',
            $bic,
            mb_substr($owner, 0, 70),
            $iban,
            'EUR'.number_format($amount, 2, '.', ''),
            '',
            '',
            mb_substr($verwendungszweck, 0, 140),
        ]);

        try {
            $qrCode = new QrCode(
                data: $epcContent,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 220,
                margin: 6,
            );
            return (new PngWriter())->write($qrCode)->getString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @deprecated Verwende buildEpcQrPng() – data:-URIs werden in E-Mail-Clients blockiert. */
    public static function buildEpcQrDataUri(
        string $bic,
        string $owner,
        string $iban,
        float  $amount,
        string $verwendungszweck,
    ): ?string {
        $png = self::buildEpcQrPng($bic, $owner, $iban, $amount, $verwendungszweck);
        return $png ? 'data:image/png;base64,'.base64_encode($png) : null;
    }
}
