{{-- Abenteuer-Detail (Waldritter v1). Wird als Vollseite (show) und im globalen Modal geladen;
     deshalb eigener .wr-v1-Wrapper. Tab-/Akkordeon-Mechanik und data-tab-Werte bleiben erhalten. --}}
<div class="wr-v1">
<span data-modal-title hidden>{{ $adventure->name }}</span>

<x-wr.booking-blocked-notice :adventure="$adventure" :profile-complete="$profileComplete"
                             :user-has-players="$userHasPlayers" class="mb-4" />

{{-- Die eigene Teamer-Anmeldung (Abrechnung, Issue #19) zählt mit, damit der Abschnitt auffällt --}}
@php($ownBookingsCount = $visibleBookings->count() + ($myTeamerSignup ? 1 : 0))
@php($bookingsTitle = 'Deine Anmeldungen (' . $ownBookingsCount . ')')

{{-- ARCH-003 Pilot: Mobile Accordion (< sm) — Akkordeon statt Tabs auf kleinen Bildschirmen --}}
<div class="sm:hidden space-y-2">
    <x-mobile.accordion-section title="Abenteuer" :open="true">
        @include('adventures._event_info')
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="$bookingsTitle" :open="$ownBookingsCount > 0">
        @include('adventures._own_bookings')
    </x-mobile.accordion-section>

    @can('view-event-heroes')
        <x-mobile.accordion-section :title="'Helden (' . $eventHeroes->count() . ')'">
            @include('adventures._heroes_tab')
        </x-mobile.accordion-section>
    @endcan

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
        <a class="item" data-tab="bookings">{{ $bookingsTitle }}</a>
        @can('view-event-heroes')
            <a class="item" data-tab="heroes">Helden ({{ $eventHeroes->count() }})</a>
        @endcan
        @can('manage-attendance')
            <a class="item" data-tab="teamer">Teamer ({{ $teamerSignups->count() }})</a>
        @endcan
    </div>

    <div class="ui bottom attached tab segment active" data-tab="event">
        @include('adventures._event_info')
    </div>

    <div class="ui bottom attached tab segment" data-tab="bookings">
        @include('adventures._own_bookings')
    </div>

    @can('view-event-heroes')
        <div class="ui bottom attached tab segment" data-tab="heroes">
            @include('adventures._heroes_tab')
        </div>
    @endcan

    @can('manage-attendance')
        <div class="ui bottom attached tab segment" data-tab="teamer">
            @include('adventures._teamer_tab', ['teamerSignups' => $teamerSignups, 'myTeamerSignup' => $myTeamerSignup])
        </div>
    @endcan
</div>

{{-- Aktionen für den Modal-Fuß (werden beim Laden dorthin verschoben). Der Sperrgrund
     steht sichtbar im Hinweis oben; der Button ist dann deaktiviert. --}}
<div data-modal-actions hidden>
    @can('adventure.book')
        @if ($adventure->registrationOpen())
            @if (! $profileComplete || ! $userHasPlayers)
                <button type="button" class="wr-btn wr-btn--primary" disabled>Anmelden</button>
            @else
                <button type="button"
                        data-modal-stack="{{ route('adventures.bookings.create', $adventure) }}"
                        class="wr-btn wr-btn--primary">Anmelden</button>
            @endif
            <button type="button"
                    data-modal-stack="{{ route('adventures.bookings.create-guest', $adventure) }}"
                    class="wr-btn wr-btn--secondary">Gast anmelden <span class="font-normal">(einmalig)</span></button>
        @endif
    @endcan
    @if (auth()->user()->hasAnyRole('teamer', 'lehrmeister') && $myTeamerSignup === null)
        <button type="button"
                data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}"
                class="wr-btn wr-btn--secondary"><i class="shield alternate icon m-0" aria-hidden="true"></i> Teamer-Anmeldung</button>
    @endif
    @can('events.edit')
        <a href="{{ route('adventures.manage', $adventure) }}" class="wr-btn wr-btn--secondary">Verwalten</a>
    @endcan
</div>
</div>
