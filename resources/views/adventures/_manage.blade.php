<span data-modal-title hidden>Verwaltung: {{ $adventure->name }}</span>

<div class="ui top attached tabular menu">
    <a class="item active" data-tab="data">Event-Daten</a>
    <a class="item" data-tab="bookings">Anmeldungen</a>
    <a class="item" data-tab="checkin">Check-in</a>
</div>

{{-- Tab 1: Allgemeine Event-Daten (Editor) --}}
<div class="ui bottom attached tab segment active" data-tab="data">
    <form method="POST" action="{{ route('adventures.update', $adventure) }}" data-reload>
        @method('PUT')
        @include('adventures._form')
    </form>

    @if ($adventure->event_status_id !== \App\Models\EventStatus::CANCELLED)
        <div class="mt-4 pt-4 border-t border-stone-300">
            <form method="POST" action="{{ route('adventures.cancel', $adventure) }}" data-refresh-modal
                  onsubmit="return confirm('Event wirklich absagen? Es sind danach keine Anmeldungen mehr möglich.');">
                @csrf @method('PATCH')
                <button type="submit" class="ui red basic button">Event absagen</button>
            </form>
        </div>
    @else
        <div class="ui warning message mt-4" style="display:block">Dieses Event ist abgesagt.</div>
    @endif
</div>

{{-- Tab 2: Anmeldungen mit Verwaltungsaktionen --}}
<div class="ui bottom attached tab segment" data-tab="bookings">
    <a href="{{ route('adventures.participation-csv', $adventure) }}" class="ui small button mb-3" target="_blank" rel="noopener">Belegungsreport (CSV)</a>
    @include('adventures._bookings', ['bookings' => $adventure->bookings, 'manage' => true])
</div>

{{-- Tab 3: Check-in --}}
<div class="ui bottom attached tab segment" data-tab="checkin">
    @include('adventures._checkin')
</div>
