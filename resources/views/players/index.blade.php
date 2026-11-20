<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Seitenkopf --}}
            <div class="mb-6">
                <span class="wr-eyebrow">Familie und Spieler</span>
                <h1 class="wr-heading">Deine Spieler</h1>
                <p class="wr-intro">
                    Hier verwaltest du die Spieler, die an unseren Veranstaltungen teilnehmen – auch dich selbst.
                    Bevor ein Held oder eine Anmeldung möglich ist, muss ein Spieler existieren.
                </p>
            </div>

            {{-- Suche (PLAY-09) --}}
            <form method="GET" action="{{ route('players.index') }}" role="search"
                  class="wr-card wr-toolbar">
                <div class="flex flex-wrap items-end gap-2 flex-1 min-w-0 md:max-w-xl">
                    <div class="flex-1 min-w-[12rem]">
                        <label for="players-search" class="wr-label">Spieler suchen</label>
                        <input id="players-search" type="search" name="q" value="{{ $q }}"
                               placeholder="Vor- oder Nachname" class="wr-input">
                    </div>
                    <button type="submit" class="wr-btn wr-btn--primary">
                        <i class="search icon m-0" aria-hidden="true"></i> Suchen
                    </button>
                    @if ($q !== '')
                        <a href="{{ route('players.index') }}" class="wr-btn wr-btn--secondary">Zurücksetzen</a>
                    @endif
                </div>
            </form>

            <div class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" role="list">

                {{-- Karte „Neuer Spieler“ --}}
                <div role="listitem" class="flex">
                    <a href="{{ route('players.create') }}" data-modal-url="{{ route('players.create') }}"
                       class="wr-card wr-card--link wr-card--placeholder flex-1">
                        <div class="wr-card__media save-data-hide">
                            <img src="/images/wewantyou_poster4.jpg" alt="" aria-hidden="true"
                                 loading="lazy" width="400" height="176">
                        </div>
                        <div class="wr-card__body">
                            <h2 class="wr-card__title"><i class="plus circle icon" aria-hidden="true"></i> Neuer Spieler</h2>
                            <p class="wr-card__text">Neuen Spieler erstellen – für dich selbst oder dein Kind.</p>
                        </div>
                    </a>
                </div>

                @foreach ($players as $player)
                    <div role="listitem" class="flex">
                        <x-wr.player-card :player="$player" class="flex-1" />
                    </div>
                @endforeach
            </div>

            @if ($players->isEmpty())
                <div class="wr-notice mt-6" role="note">
                    <i class="info circle icon m-0 mt-0.5" aria-hidden="true"></i>
                    <p>
                        @if ($q !== '')
                            Kein Spieler zu „{{ $q }}“ gefunden.
                        @else
                            <strong>Erste Schritte:</strong>
                            Lege zunächst einen Spieler an und melde ihn zu einem Abenteuer an. Den Helden erstellt ihr dann
                            direkt vor Ort beim Bürokraten – das ist die Person der Waldritter, die die Helden ins Heldenregister einträgt.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
