<span data-modal-title hidden>{{ $adventure->name }}</span>

<div class="ui top attached tabular menu">
    <a class="item active" data-tab="event">Abenteuer</a>
    <a class="item" data-tab="bookings">Anmeldungen</a>
    @can('manage-attendance')
        <a class="item" data-tab="teamer">Teamer ({{ $teamerSignups->count() }})</a>
    @endcan
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
        <div><dt class="text-sm text-stone-500">Spielleiter</dt><dd>{{ $adventure->gamemaster ? trim("{$adventure->gamemaster->name} {$adventure->gamemaster->lastname}") : '—' }}</dd></div>
        <div><dt class="text-sm text-stone-500">Veranstaltungsleiter</dt><dd>{{ $adventure->eventleader ? trim("{$adventure->eventleader->name} {$adventure->eventleader->lastname}") : '—' }}</dd></div>
    </dl>
</div>

{{-- Tab 2: Anmeldungen (rollenabhängig gefiltert, schreibgeschützt) --}}
<div class="ui bottom attached tab segment" data-tab="bookings">
    @include('adventures._bookings', ['bookings' => $visibleBookings, 'manage' => false])
</div>

{{-- Tab 3: Teamer-Anmeldungen (ADV-27, nur manage-attendance) --}}
@can('manage-attendance')
    <div class="ui bottom attached tab segment" data-tab="teamer">
        @include('adventures._teamer_tab', ['teamerSignups' => $teamerSignups, 'myTeamerSignup' => $myTeamerSignup])
    </div>
@endcan

<div data-modal-actions hidden>
    @can('adventure.book')
        @if ($adventure->registrationOpen())
            <a href="{{ route('adventures.bookings.create', $adventure) }}"
               data-modal-stack="{{ route('adventures.bookings.create', $adventure) }}"
               class="ui primary button">Anmelden</a>
            <a href="{{ route('adventures.bookings.create-guest', $adventure) }}"
               data-modal-stack="{{ route('adventures.bookings.create-guest', $adventure) }}"
               class="ui button" title="Für Gäste werden keine EP gesammelt">Gast anmelden</a>
        @endif
    @endcan
    @if (auth()->user()->hasAnyRole('teamer', 'lehrmeister') && $myTeamerSignup === null)
        <a href="{{ route('adventures.teamer.create', $adventure) }}"
           data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}"
           class="ui teal button">Teamer-Anmeldung</a>
    @endif
    @can('events.edit')
        <a href="{{ route('adventures.manage', $adventure) }}"
           data-modal-url="{{ route('adventures.manage', $adventure) }}"
           class="ui button">Verwalten</a>
    @endcan
</div>
