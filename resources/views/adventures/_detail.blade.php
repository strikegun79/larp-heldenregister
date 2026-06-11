<span data-modal-title hidden>{{ $adventure->name }}</span>

<div class="ui top attached tabular menu">
    <a class="item active" data-tab="event">Event</a>
    <a class="item" data-tab="bookings">Anmeldungen</a>
</div>

{{-- Tab 1: Event-Informationen --}}
<div class="ui bottom attached tab segment active" data-tab="event">
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
    </dl>
</div>

{{-- Tab 2: Anmeldungen (rollenabhängig gefiltert, schreibgeschützt) --}}
<div class="ui bottom attached tab segment" data-tab="bookings">
    @include('adventures._bookings', ['bookings' => $visibleBookings, 'manage' => false])
</div>

<div data-modal-actions hidden>
    @can('adventure.book')
        @if ($adventure->registrationOpen())
            <a href="{{ route('adventures.bookings.create', $adventure) }}"
               data-modal-subview="{{ route('adventures.bookings.create', $adventure) }}"
               class="ui primary button">Anmelden</a>
        @endif
    @endcan
    @can('events.edit')
        <a href="{{ route('adventures.manage', $adventure) }}"
           data-modal-url="{{ route('adventures.manage', $adventure) }}"
           class="ui button">Verwalten</a>
    @endcan
</div>
