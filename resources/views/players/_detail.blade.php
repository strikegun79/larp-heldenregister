{{-- Spieler-Detail (Waldritter v1). Wird als Vollseite (show) und im globalen Modal geladen;
     deshalb eigener .wr-v1-Wrapper. Jeder Bereich ist nur einmal geschrieben (players/partials)
     und wird für Akkordeon (Handy) und Reiter (Desktop) eingebunden. --}}
<div class="wr-v1">
<span data-modal-title hidden>{{ $player->full_name }}</span>

@php($canEdit = auth()->user()?->can('update', $player))

{{-- UI-40: Mobile Accordion (< sm) --}}
<div class="sm:hidden space-y-2">
    <x-mobile.accordion-section title="Allgemeines" :open="true">
        @include('players.partials._general', ['ctx' => 'm'])
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Helden (' . $player->heroes->count() . ')'">
        @include('players.partials._heroes')
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Abenteuer (' . $player->visits->count() . ')'">
        @include('players.partials._visits')
    </x-mobile.accordion-section>

    {{-- N-2: Fotoerlaubnis-Verwaltung je Buchung --}}
    @if ($player->bookings->isNotEmpty())
        <x-mobile.accordion-section :title="'Fotoerlaubnis (' . $player->bookings->count() . ')'">
            @include('players.partials._photo_consent')
        </x-mobile.accordion-section>
    @endif
</div>

{{-- Desktop: Fomantic-Tabs (sm+) --}}
<div class="hidden sm:block">
    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="p-allg">Allgemeines</a>
        <a class="item" data-tab="p-helden">Helden ({{ $player->heroes->count() }})</a>
        <a class="item" data-tab="p-abenteuer">Abenteuer ({{ $player->visits->count() }})</a>
        @if ($player->bookings->isNotEmpty())
            <a class="item" data-tab="p-buchungen">Fotoerlaubnis ({{ $player->bookings->count() }})</a>
        @endif
    </div>

    <div class="ui bottom attached tab segment active" data-tab="p-allg">
        @include('players.partials._general', ['ctx' => 'd'])
    </div>

    <div class="ui bottom attached tab segment" data-tab="p-helden">
        @include('players.partials._heroes')
    </div>

    <div class="ui bottom attached tab segment" data-tab="p-abenteuer">
        @include('players.partials._visits')
    </div>

    @if ($player->bookings->isNotEmpty())
        <div class="ui bottom attached tab segment" data-tab="p-buchungen">
            @include('players.partials._photo_consent')
        </div>
    @endif
</div>
</div>
