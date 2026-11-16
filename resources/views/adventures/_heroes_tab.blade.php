{{--
    Helden-Tab: aktive Helden der angemeldeten Teilnehmer mit Klassen.
    Hilft Lehrmeistern und Plotschreibern, den Plot auf die Fertigkeiten
    der Helden abzustimmen.
    Variablen: $eventHeroes (Collection<Booking>, Spieler + aktiver Held + Klassen geladen)
--}}

@if ($eventHeroes->isEmpty())
    <p class="text-stone-500 text-sm">Noch keine Teilnehmer angemeldet.</p>
@else
    @php
        // Klassenverteilung über alle aktiven Helden (für schnellen Überblick).
        $classCounts = $eventHeroes
            ->map(fn ($b) => $b->player->activeHero)
            ->filter()
            ->flatMap(fn ($hero) => $hero->classes)
            ->countBy('name')
            ->sortKeys();
    @endphp

    @if ($classCounts->isNotEmpty())
        <div class="mb-3 text-sm text-stone-600">
            <span class="font-semibold">Klassenverteilung:</span>
            @foreach ($classCounts as $name => $count)
                <span class="ui tiny basic label">{{ $name }} <span class="detail">{{ $count }}</span></span>
            @endforeach
        </div>
    @endif

    <x-mobile.cards-or-table>
    <table class="ui very basic compact unstackable table">
        <thead class="mob-thead" hidden>
            <tr>
                <th>Held</th>
                <th>Spieler</th>
                <th>Klassen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($eventHeroes as $booking)
                @php $hero = $booking->player->activeHero; @endphp
                <tr>
                    <td data-label="Held">
                        @if ($hero)
                            {{-- Ohne Heldennamen: Spielername kursiv als Linktext --}}
                            <a href="{{ route('heroes.show', $hero) }}" target="_blank" rel="noopener"
                               class="{{ filled($hero->character_name) ? 'font-semibold' : 'font-normal' }} text-waldritter hover:underline">
                                @if (filled($hero->character_name))
                                    {{ $hero->character_name }}
                                @else
                                    <em>{{ $booking->player->full_name }}</em>
                                @endif
                            </a>
                        @else
                            <span class="text-stone-500 italic">kein aktiver Held</span>
                        @endif
                    </td>
                    <td data-label="Spieler">{{ $booking->player->full_name }}</td>
                    <td data-label="Klassen">
                        @forelse ($hero?->classes ?? [] as $class)
                            <span class="ui small label">{{ $class->name }}</span>
                        @empty
                            <span class="text-stone-500">—</span>
                        @endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </x-mobile.cards-or-table>

@endif
