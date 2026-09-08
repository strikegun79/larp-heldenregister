<x-mail::message>
# Hallo {{ $booking->player?->full_name ?: 'Abenteurer' }}!

Deine Anmeldung für **{{ $booking->adventure?->name }}** ist eingegangen.

**Rolle:** {{ $booking->role?->description ?? '—' }}

---

@if ($ageViolation)
Dein Alter liegt außerhalb der Teilnehmeraltersgrenze für dieses Abenteuer
({{ $booking->adventure?->age_range_label }}). Deine Teilnahme muss daher **manuell bestätigt** werden – du stehst auf der **Warteliste**.

Sobald deine Anmeldung geprüft und freigegeben wurde, erhältst du eine Bestätigungs-E-Mail mit der Zahlungsaufforderung und den Bankdaten.
@else
Das Abenteuer ist derzeit ausgebucht – du stehst auf der **Warteliste**.

Sobald ein Platz frei wird, rückst du automatisch nach. Du erhältst dann eine weitere E-Mail mit der Zahlungsaufforderung und den Bankdaten.
@endif

Bitte überweise noch **keinen Beitrag**, bevor du die Bestätigungs-E-Mail erhalten hast.

<x-mail::button :url="$dashboardUrl">
Zum Heldenregister
</x-mail::button>

Schönen Gruß,
Deine Orga
</x-mail::message>
