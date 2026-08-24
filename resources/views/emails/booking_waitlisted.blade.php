<x-mail::message>
# Hallo {{ $booking->player?->full_name ?: 'Abenteurer' }}!

Deine Anmeldung für **{{ $booking->adventure?->name }}** ist eingegangen.

**Rolle:** {{ $booking->role?->description ?? '—' }}

---

Das Abenteuer ist derzeit ausgebucht – du stehst auf der **Warteliste**.

Sobald ein Platz frei wird, rückst du automatisch nach. Du erhältst dann eine weitere E-Mail mit der Zahlungsaufforderung und den Bankdaten.

Bitte überweise noch **keinen Beitrag**, bevor du die Bestätigungs-E-Mail erhalten hast.

<x-mail::button :url="$dashboardUrl">
Zum Heldenregister
</x-mail::button>

Schönen Gruß,
Deine Orga
</x-mail::message>
