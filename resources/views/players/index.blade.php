<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Deine Spieler</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 mb-6 text-stone-700">
                Hier verwaltest du die Spieler, die an unseren Veranstaltungen teilnehmen – auch dich selbst.
                Bevor ein Held oder eine Anmeldung möglich ist, muss ein Spieler existieren.
            </div>

            {{-- Suche (PLAY-09) --}}
            <form method="GET" action="{{ route('players.index') }}" class="ui form mb-6">
                <div class="ui action input w-full sm:w-80">
                    <input type="search" name="q" value="{{ $q }}" placeholder="Name suchen…" class="!rounded-l-full">
                    <button type="submit" class="ui icon button" aria-label="Suchen"><i class="search icon"></i></button>
                </div>
                @if ($q !== '')
                    <a href="{{ route('players.index') }}" class="ml-3 text-sm text-stone-500 hover:underline">Filter zurücksetzen</a>
                @endif
            </form>

            {{-- Spielerkarten: Avatar links (150×150), Daten rechts (PLAY-11) --}}
            <div class="grid gap-4 grid-cols-1 md:grid-cols-2">

                {{-- Karte „Neuer Spieler" --}}
                <a href="{{ route('players.create') }}" data-modal-url="{{ route('players.create') }}"
                   class="group flex rounded-lg overflow-hidden border-2 border-dashed border-[#5a3a22]/50 bg-white/50 shadow hover:shadow-xl hover:-translate-y-1 transition min-h-[150px]">
                    <div class="shrink-0 overflow-hidden" style="width:150px; min-height:150px;">
                        <img src="/images/wewantyou_poster4.jpg" alt="Neuer Spieler"
                             class="w-full h-full object-cover group-hover:scale-105 transition" style="min-height:150px;">
                    </div>
                    <div class="p-4 flex-1 flex flex-col justify-center">
                        <div class="font-uncial text-xl text-waldritter">Neuer Spieler</div>
                        <div class="text-sm text-stone-600 mt-1">Neuen Spieler erstellen</div>
                    </div>
                </a>

                {{-- Spielerkarten mit Papyrus-Hintergrund --}}
                @foreach ($players as $player)
                    <a href="{{ route('players.show', $player) }}" data-modal-url="{{ route('players.show', $player) }}"
                       class="group flex rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 shadow hover:shadow-xl hover:-translate-y-1 transition min-h-[150px]"
                       style="background-image:url('/images/player_background.png'); background-size:cover; background-position:center; background-color:#efe4cf;">
                        {{-- Avatar 1:1, linksbündig --}}
                        <div class="shrink-0 overflow-hidden" style="width:150px; min-height:150px;">
                            <img src="{{ $player->avatar_url }}" alt="{{ $player->full_name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition" style="min-height:150px;">
                        </div>
                        {{-- Name + Spielerdaten --}}
                        <div class="p-4 flex-1">
                            <div class="font-uncial text-lg text-waldritter leading-snug">
                                {{ $player->full_name }}
                                @if ($player->pivot->self)
                                    <span class="align-middle rounded bg-[#5a3a22] text-amber-50 text-xs px-2 py-0.5">Du</span>
                                @endif
                            </div>
                            <dl class="mt-2 text-sm text-stone-700 space-y-0.5">
                                <div><span class="text-stone-500">Erstellt:</span> {{ optional($player->created_at)->format('d.m.Y') ?? '—' }}</div>
                                <div><span class="text-stone-500">Alter:</span> {{ $player->age !== null ? $player->age.' Jahre' : '—' }}</div>
                                <div><span class="text-stone-500">Geschlecht:</span> {{ $player->gender ?? '—' }}</div>
                                <div><span class="text-stone-500">Besuchte Events:</span> {{ $player->visits_count }}</div>
                            </dl>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
