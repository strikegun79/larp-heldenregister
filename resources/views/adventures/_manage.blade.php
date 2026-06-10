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
</div>

{{-- Tab 2: Anmeldungen mit Verwaltungsaktionen --}}
<div class="ui bottom attached tab segment" data-tab="bookings">
    @include('adventures._bookings', ['bookings' => $adventure->bookings, 'manage' => true])
</div>

{{-- Tab 3: Check-in --}}
<div class="ui bottom attached tab segment" data-tab="checkin">
    @include('adventures._checkin')
</div>
