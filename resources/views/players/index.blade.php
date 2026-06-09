<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Deine Spieler</h2>
            <a href="{{ route('players.create') }}"><x-primary-button>Neuer Spieler</x-primary-button></a>
        </div>
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

            @if ($players->isEmpty())
                <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-6 text-center text-stone-600">
                    Noch keine Spieler angelegt.
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($players as $player)
                        <div class="rounded-lg border-2 border-[#5a3a22]/40 bg-white/70 shadow p-5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <a href="{{ route('players.show', $player) }}" class="font-uncial text-lg text-waldritter hover:underline">
                                        {{ $player->full_name }}
                                    </a>
                                    @if ($player->pivot->self)
                                        <span class="ml-1 align-middle rounded bg-[#5a3a22] text-amber-50 text-xs px-2 py-0.5">Du</span>
                                    @endif
                                    <div class="text-sm text-stone-600">{{ $player->gender ?? '—' }}</div>
                                </div>
                                <a href="{{ route('players.edit', $player) }}" class="text-sm text-stone-600 hover:underline">Bearbeiten</a>
                            </div>

                            <div class="mt-3 text-sm text-stone-700">
                                <div class="font-semibold">Helden:</div>
                                @forelse ($player->heroes as $hero)
                                    <div>• {{ $hero->character_name ?? '—' }}
                                        <span class="text-stone-500">({{ $hero->class_list ?: 'keine Klasse' }})</span>
                                    </div>
                                @empty
                                    <div class="text-stone-500">noch keine Helden</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
