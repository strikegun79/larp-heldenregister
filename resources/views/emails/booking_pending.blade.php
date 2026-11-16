<x-mail::message>
# Hallo {{ $booking->player?->full_name ?: 'Abenteurer' }}!

Deine Anmeldung für **{{ $booking->adventure?->name }}** ist bei uns eingegangen.

**Rolle:** {{ $booking->role?->description ?? '—' }}

<x-mail::panel>
**Hinweis:** Anmeldungen für dieses Abenteuer werden einzeln geprüft und bestätigt.
Sobald deine Anmeldung bestätigt ist, erhältst du eine weitere E-Mail mit dem Teilnahmebeitrag und den Bankdaten.
</x-mail::panel>

Bitte überweise noch **keinen Beitrag**, bevor du die Bestätigungs-E-Mail erhalten hast.

<x-mail::button :url="$dashboardUrl">
Zum Heldenregister
</x-mail::button>

Schönen Gruß,
Deine Orga
</x-mail::message>
