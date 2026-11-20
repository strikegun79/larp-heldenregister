{{-- Waldritter v1: Spielerkarte für „Deine Spieler“. Papyrus-Motiv (player_background.png) als
     Textur nur im Kopf hinter Avatar und Name, die Angaben stehen auf ruhigem Papier.
     Ganze Karte öffnet das Spieler-Detail (Modal, ohne Skript die Seite).
     Erwartet einen Spieler mit Pivot "self", "activeHero" (+ "heroes.classes") und "visits_count". --}}
@props(['player'])

@php
    $activeHero = $player->activeHero;
    $activeHeroClasses = $activeHero ? ($player->heroes->firstWhere('id', $activeHero->id)?->classes ?? collect()) : collect();
@endphp

<a href="{{ route('players.show', $player) }}" data-modal-url="{{ route('players.show', $player) }}"
   {{ $attributes->merge(['class' => 'wr-card wr-card--link']) }}>
    {{-- Kopf mit Papyrus-Textur --}}
    <div class="wr-player-card__head"
         style="background-image: url('/images/player_background.png');">
        <img src="{{ $player->avatar_url }}" alt="" aria-hidden="true" loading="lazy" width="96" height="96"
             class="w-20 h-20 sm:w-24 sm:h-24 shrink-0 object-cover rounded-xl border-2 border-wr-border bg-wr-paper">
        <div class="min-w-0">
            <h2 class="wr-card__title break-words hyphens-auto">{{ $player->full_name }}</h2>
            @if ($player->pivot?->self)
                <div class="mt-1.5"><span class="wr-pill wr-pill--info wr-pill--small"><i class="user icon" aria-hidden="true"></i>Du</span></div>
            @endif
        </div>
    </div>

    <div class="wr-card__body">
        <dl class="wr-facts wr-facts--quiet">
            <div>
                <dt><i class="birthday cake icon" aria-hidden="true"></i> Alter</dt>
                <dd>{{ $player->age !== null ? $player->age . ' Jahre' : '—' }}</dd>
            </div>
            <div>
                <dt><i class="map icon" aria-hidden="true"></i> Abenteuer</dt>
                <dd>{{ $player->visits_count }} Abenteuer erlebt</dd>
            </div>
            <div class="min-[520px]:col-span-2">
                <dt><i class="chess knight icon" aria-hidden="true"></i> Aktiver Held</dt>
                <dd>
                    @if ($activeHero)
                        <span class="font-semibold break-words">{{ $activeHero->character_name }}</span>
                        @if ($activeHeroClasses->isNotEmpty())
                            <span class="flex flex-wrap gap-1.5 mt-1">
                                @foreach ($activeHeroClasses as $heroClass)
                                    <x-wr.class-badge :hero-class="$heroClass" />
                                @endforeach
                            </span>
                        @endif
                    @else
                        <span class="text-wr-ink-muted">noch kein Held – er entsteht beim ersten Abenteuer</span>
                    @endif
                </dd>
            </div>
        </dl>
        <span class="wr-card__hint justify-end mt-2 font-semibold text-wr-forest">
            Details ansehen <i class="chevron right icon m-0" aria-hidden="true"></i>
        </span>
    </div>
</a>
