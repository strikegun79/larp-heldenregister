<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Deine Spieler</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 mb-6 text-stone-700">
                Hier verwaltest du die Spieler, die an unseren Veranstaltungen teilnehmen – auch dich selbst.
                Bevor ein Held oder eine Anmeldung möglich ist, muss ein Spieler existieren.
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Karte „Neuer Spieler" (PLAY-10) --}}
                <a href="{{ route('players.create') }}" data-modal-url="{{ route('players.create') }}"
                   class="group block rounded-lg overflow-hidden border-2 border-dashed border-[#5a3a22]/50 bg-white/50 shadow hover:shadow-xl hover:-translate-y-1 transition">
                    <div class="h-48 overflow-hidden">
                        <img src="/images/wewantyou_poster4.jpg" alt="Neuer Spieler" class="w-full h-full object-cover group-hover:scale-105 transition">
                    </div>
                    <div class="p-4 text-center">
                        <div class="font-uncial text-lg text-waldritter">Neuer Spieler</div>
                        <div class="text-sm text-stone-600">Neuen Spieler erstellen</div>
                    </div>
                </a>

                {{-- Spielerkarten (Steckbrief-Papyrus als Karten-Hintergrund, PLAY-11) --}}
                @foreach ($players as $player)
                    <a href="{{ route('players.show', $player) }}" data-modal-url="{{ route('players.show', $player) }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 shadow hover:shadow-xl hover:-translate-y-1 transition"
                       style="background-image:url('/images/player_background.png'); background-size:cover; background-position:center; background-color:#efe4cf;">
                        <div class="h-48 overflow-hidden">
                            <img src="{{ $player->avatar_url }}" alt="{{ $player->full_name }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4">
                            <div class="font-uncial text-lg text-waldritter">
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
