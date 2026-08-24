@php
use App\Models\Setting;
use App\Notifications\BookingReceived;

$fee   = $booking->effectiveFee();
$bankData        = null;
$verwendungszweck = null;
$qrDataUri       = null;

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
            $qrDataUri = BookingReceived::buildEpcQrDataUri($bic, $owner, $iban, $fee, $verwendungszweck);
        }
    }
}
@endphp
<x-mail::message>
# Gute Nachricht, {{ $booking->player?->full_name ?: 'Abenteurer' }}!

Für **{{ $booking->adventure?->name }}** ist ein Platz frei geworden – du bist von der Warteliste nachgerückt und nimmst jetzt regulär teil. 🎉

**Rolle:** {{ $booking->role?->description ?? '—' }}

@if ($fee > 0)

---

Bitte überweise deinen Teilnahmebeitrag von **{{ number_format($fee, 2, ',', '.') }} €**@if ($booking->ermaessigung)  *(ermäßigt – Nachweis beim Check-in erforderlich)*@endif:

@if ($bankData)

| | |
|---|---|
@if ($bankData['owner'])
| Kontoinhaber | {{ $bankData['owner'] }} |
@endif
| IBAN | `{{ $bankData['iban'] }}` |
@if ($bankData['bic'])
| BIC | {{ $bankData['bic'] }} |
@endif
@if ($bankData['bank'])
| Bank | {{ $bankData['bank'] }} |
@endif
| Verwendungszweck | **{{ $verwendungszweck }}** |

@if ($qrDataUri)
<x-mail::panel>

**GiroCode – Schnell per Banking-App zahlen**

Scanne diesen QR-Code mit deiner Banking-App, um die Überweisung direkt vorzubereiten:

<div style="text-align:center;margin:12px 0;">
<img src="{{ $qrDataUri }}" alt="GiroCode für die Überweisung" width="200" height="200" style="display:inline-block;border:1px solid #ddd;border-radius:6px;padding:4px;">
</div>

Betrag, IBAN und Verwendungszweck sind bereits ausgefüllt – bitte vor dem Absenden prüfen.

</x-mail::panel>
@endif

@endif
@endif

<x-mail::button :url="$dashboardUrl">
Zum Heldenregister
</x-mail::button>

Schönen Gruß,
Deine Orga
</x-mail::message>
