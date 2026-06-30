<span data-modal-title hidden>Verwaltung: {{ $adventure->name }}</span>

{{-- UI-40: Mobile Accordion (< sm) --}}
<div class="sm:hidden space-y-2">
    <x-mobile.accordion-section title="Event-Daten" :open="true">
        <form id="manage-adventure-form-mobile" method="POST" action="{{ route('adventures.update', $adventure) }}" data-reload>
            @method('PUT')
            @include('adventures._form', ['inModal' => true])
        </form>
        @if ($adventure->event_status_id !== \App\Models\EventStatus::CANCELLED)
            <div class="ui red segment mt-4">
                <h5 class="ui header" style="color: #9b2c2c;">
                    <i class="ban icon"></i>
                    <div class="content">Gefahrenzone</div>
                </h5>
                <form method="POST" action="{{ route('adventures.cancel', $adventure) }}" data-refresh-modal
                      data-confirm="Abenteuer wirklich absagen? Es sind danach keine Anmeldungen mehr möglich.">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui red basic button">
                        <i class="ban icon"></i> Abenteuer absagen
                    </button>
                </form>
            </div>
        @else
            <div class="ui warning message mt-4" style="display:block">Dieses Event ist abgesagt.</div>
        @endif
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Anmeldungen (' . $mainBookings->count() . ')'">
        <a href="{{ route('adventures.participation-csv', $adventure) }}" class="ui small button mb-3" target="_blank" rel="noopener">Belegungsreport (CSV)</a>
        @include('adventures._bookings', ['bookings' => $mainBookings, 'manage' => true])
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Teamer/NSC (' . ($adventure->teamerSignups->count() + $nscBookings->count()) . ')'">
        @include('adventures._teamer_nsc_tab', [
            'teamerSignups' => $adventure->teamerSignups,
            'nscBookings'   => $nscBookings,
        ])
        <div class="mt-4 pt-4 border-t border-stone-200">
            <form method="POST" action="{{ route('adventures.teamer.invite', $adventure) }}"
                  data-confirm="Einladung an alle aktiven Teamer und Lehrmeister schicken?">
                @csrf
                <button type="submit" class="ui teal button">
                    <i class="mail icon"></i> Teamer einladen
                </button>
                <p class="text-sm text-stone-500 mt-1">Benachrichtigt alle aktiven Teamer &amp; Lehrmeister mit eingeschalteten Benachrichtigungen.</p>
            </form>
        </div>
    </x-mobile.accordion-section>

    <x-mobile.accordion-section title="Check-in">
        @include('adventures._checkin')
    </x-mobile.accordion-section>
</div>

{{-- Desktop: Fomantic-Tabs (sm+) --}}
<div class="hidden sm:block">
    <div class="ui top attached tabular menu" style="overflow-x: auto; flex-wrap: nowrap;">
        <a class="item active" data-tab="data" style="white-space: nowrap;">Event-Daten</a>
        <a class="item" data-tab="bookings" style="white-space: nowrap;">Anmeldungen ({{ $mainBookings->count() }})</a>
        <a class="item" data-tab="teamer-nsc" style="white-space: nowrap;">Teamer/NSC ({{ $adventure->teamerSignups->count() + $nscBookings->count() }})</a>
        <a class="item" data-tab="checkin" style="white-space: nowrap;">Check-in</a>
    </div>

    <div class="ui bottom attached tab segment active" data-tab="data">
        <form id="manage-adventure-form" method="POST" action="{{ route('adventures.update', $adventure) }}" data-reload>
            @method('PUT')
            @include('adventures._form', ['inModal' => true])
        </form>
        @if ($adventure->event_status_id !== \App\Models\EventStatus::CANCELLED)
            <div class="ui red segment mt-4">
                <h5 class="ui header" style="color: #9b2c2c;">
                    <i class="ban icon"></i>
                    <div class="content">Gefahrenzone</div>
                </h5>
                <form method="POST" action="{{ route('adventures.cancel', $adventure) }}" data-refresh-modal
                      data-confirm="Abenteuer wirklich absagen? Es sind danach keine Anmeldungen mehr möglich.">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui red basic button">
                        <i class="ban icon"></i> Abenteuer absagen
                    </button>
                </form>
            </div>
        @else
            <div class="ui warning message mt-4" style="display:block">Dieses Event ist abgesagt.</div>
        @endif
    </div>

    <div class="ui bottom attached tab segment" data-tab="bookings">
        <a href="{{ route('adventures.participation-csv', $adventure) }}" class="ui small button mb-3" target="_blank" rel="noopener">Belegungsreport (CSV)</a>
        @include('adventures._bookings', ['bookings' => $mainBookings, 'manage' => true])
    </div>

    <div class="ui bottom attached tab segment" data-tab="teamer-nsc">
        @include('adventures._teamer_nsc_tab', [
            'teamerSignups' => $adventure->teamerSignups,
            'nscBookings'   => $nscBookings,
        ])
        <div class="mt-4 pt-4 border-t border-stone-200">
            <form method="POST" action="{{ route('adventures.teamer.invite', $adventure) }}"
                  data-confirm="Einladung an alle aktiven Teamer und Lehrmeister schicken?">
                @csrf
                <button type="submit" class="ui teal button">
                    <i class="mail icon"></i> Teamer einladen
                </button>
                <span class="text-sm text-stone-500 ml-2">Benachrichtigt alle aktiven Teamer &amp; Lehrmeister mit eingeschalteten Benachrichtigungen.</span>
            </form>
        </div>
    </div>

    <div class="ui bottom attached tab segment" data-tab="checkin">
        @include('adventures._checkin')
    </div>
</div>

<div data-modal-actions hidden>
    <button type="submit" form="manage-adventure-form" class="ui primary button">
        <i class="save icon"></i> Speichern
    </button>
</div>
