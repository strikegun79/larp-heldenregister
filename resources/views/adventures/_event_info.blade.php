<dl class="grid grid-cols-2 gap-4 text-stone-800">
    <div><dt class="text-sm text-stone-500">Beginn</dt><dd>{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</dd></div>
    <div><dt class="text-sm text-stone-500">Ende</dt><dd>{{ optional($adventure->end_at)->format('d.m.Y H:i') }}</dd></div>
    <div><dt class="text-sm text-stone-500">Ort</dt><dd>{{ $adventure->location?->titel ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Status</dt><dd>@include('adventures._status_badge', ['status' => $adventure->status])</dd></div>
    <div><dt class="text-sm text-stone-500">Kategorie</dt><dd>{{ $adventure->category?->name }}</dd></div>
    <div><dt class="text-sm text-stone-500">Auftraggeber</dt><dd>{{ $adventure->client?->name }}</dd></div>
    <div><dt class="text-sm text-stone-500">Beitrag</dt><dd>{{ number_format($adventure->fee, 2, ',', '.') }} €</dd></div>
    <div><dt class="text-sm text-stone-500">Belegung</dt><dd class="font-semibold">{{ $adventure->confirmedBookings()->count() }} / {{ $adventure->max_player }} ({{ $adventure->freeSlots() }} frei)</dd></div>
    @if ($adventure->function_email)
        <div><dt class="text-sm text-stone-500">Funktions-E-Mail</dt><dd><a href="mailto:{{ $adventure->function_email }}" class="text-waldritter hover:underline">{{ $adventure->function_email }}</a></dd></div>
    @endif
    <div><dt class="text-sm text-stone-500">Spielleiter</dt><dd>{{ $adventure->gamemaster ? trim("{$adventure->gamemaster->name} {$adventure->gamemaster->lastname}") : '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Veranstaltungsleiter</dt><dd>{{ $adventure->eventleader ? trim("{$adventure->eventleader->name} {$adventure->eventleader->lastname}") : '—' }}</dd></div>
</dl>
