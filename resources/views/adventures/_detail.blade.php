<span data-modal-title hidden>{{ $adventure->name }}</span>

{{-- ARCH-003 Pilot: Mobile Accordion (< sm) — Akkordeon statt Tabs auf kleinen Bildschirmen --}}
<div class="sm:hidden space-y-2">
    <x-mobile.accordion-section title="Abenteuer" :open="true">
        @include('adventures._event_info')
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Anmeldungen (' . $visibleBookings->count() . ')'">
        @include('adventures._bookings', ['bookings' => $visibleBookings, 'manage' => false])
    </x-mobile.accordion-section>

    @can('manage-attendance')
        <x-mobile.accordion-section :title="'Teamer (' . $teamerSignups->count() . ')'">
            @include('adventures._teamer_tab', ['teamerSignups' => $teamerSignups, 'myTeamerSignup' => $myTeamerSignup])
        </x-mobile.accordion-section>
    @endcan
</div>

{{-- Desktop: Fomantic-Tabs (>= sm) --}}
<div class="hidden sm:block">
    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="event">Abenteuer</a>
        <a class="item" data-tab="bookings">Anmeldungen</a>
        @can('manage-attendance')
            <a class="item" data-tab="teamer">Teamer ({{ $teamerSignups->count() }})</a>
        @endcan
    </div>

    <div class="ui bottom attached tab segment active" data-tab="event">
        @include('adventures._event_info')
    </div>

    <div class="ui bottom attached tab segment" data-tab="bookings">
        @include('adventures._bookings', ['bookings' => $visibleBookings, 'manage' => false])
    </div>

    @can('manage-attendance')
        <div class="ui bottom attached tab segment" data-tab="teamer">
            @include('adventures._teamer_tab', ['teamerSignups' => $teamerSignups, 'myTeamerSignup' => $myTeamerSignup])
        </div>
    @endcan
</div>

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
        <a href="{{ route('adventures.manage', $adventure) }}" class="ui button">Verwalten</a>
    @endcan
</div>
