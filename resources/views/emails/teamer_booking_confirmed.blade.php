<x-mail::message>
# Hallo {{ $playerName }}!

Deine Teamer-Anmeldung für **{{ $adventureName }}** als **{{ $roleName }}** wurde bestätigt. Herzlichen Dank, dass du dabei bist!

@if ($startDate)
**Datum:** {{ $startDate }}@if ($endDate && $endDate !== $startDate) – {{ $endDate }}@endif
@endif

<x-mail::panel>
Spielleiter und/oder Projektleiter werden dir rechtzeitig weitere Informationen zum genauen Ablauf, Treffpunkt und Aufgaben zukommen lassen.
</x-mail::panel>

<x-mail::button :url="$dashboardUrl">
Zum Heldenregister
</x-mail::button>

Herzlichen Dank und viele Grüße,
Deine Orga
</x-mail::message>
