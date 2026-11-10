<x-mail::message>
# Hallo {{ $booking->player?->full_name ?: 'Abenteurer' }}!

Vielen Dank für deine Bereitschaft, bei **{{ $booking->adventure?->name }}** als **{{ $booking->role?->description ?? 'Teamer' }}** mitzuwirken!

Deine Anmeldung ist bei uns eingegangen.

<x-mail::panel>
**Hinweis:** Teamer-Anmeldungen werden manuell geprüft und bestätigt. Du erhältst eine separate Nachricht, sobald der Projektleiter deine Anmeldung freigegeben hat.
</x-mail::panel>

Spielleiter und/oder Projektleiter werden dir rechtzeitig weitere Informationen zum genauen Ablauf zukommen lassen.

<x-mail::button :url="$dashboardUrl">
Zum Heldenregister
</x-mail::button>

Herzlichen Dank und viele Grüße,
Deine Orga
</x-mail::message>
