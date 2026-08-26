<x-mail::message>
# Hallo {{ $booking->player?->full_name ?: 'Abenteurer' }}!

Deine Anmeldung für **{{ $booking->adventure?->name }}** ist eingegangen.

**Rolle:** {{ $booking->role?->description ?? '—' }}

@if ($booking->waitlisted)
> **Hinweis:** Das Abenteuer ist derzeit ausgebucht – du stehst auf der Warteliste und rückst bei einem frei werdenden Platz automatisch nach.
@endif

@if ($fee > 0)

---

**Zu zahlender Beitrag:** {{ number_format($fee, 2, ',', '.') }} €@if ($booking->ermaessigung)  *(ermäßigt – Nachweis beim Check-in erforderlich)*@endif

@if ($bankData)

**Bankverbindung:**

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

@if ($qrCid)
<x-mail::panel>

**GiroCode – Schnell per Banking-App zahlen**

Scanne diesen QR-Code mit deiner Banking-App (z. B. Sparkasse, DKB, ING, Volksbank), um die Überweisung direkt vorzubereiten:

<div style="text-align:center;margin:12px 0;">
<img src="{{ $qrCid }}" alt="GiroCode für die Überweisung" width="200" height="200" style="display:inline-block;border:1px solid #ddd;border-radius:6px;padding:4px;">
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
