<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Heldenregister
            </h2>
            @can('heldenregister.edit')
                <a href="{{ route('heroes.create') }}">
                    <x-primary-button>Neuer Held</x-primary-button>
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php($selectClass = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-amber-600 focus:ring-amber-600')
            <form method="GET" action="{{ route('heroes.index') }}"
                  class="mb-4 bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
                <div class="lg:col-span-2">
                    <label class="text-sm text-stone-600">Suche (Spieler-, Charakter- oder Fertigkeitsname)</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="z. B. Tilix, Müller oder Erste Hilfe" class="{{ $selectClass }}">
                </div>
                <div>
                    <label class="text-sm text-stone-600">Klasse</label>
                    <select name="class_id" class="{{ $selectClass }}">
                        <option value="">Alle</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-stone-600">Spieler</label>
                    <select name="player_id" class="{{ $selectClass }}">
                        <option value="">Alle</option>
                        @foreach ($players as $player)
                            <option value="{{ $player->id }}" @selected((string) $playerId === (string) $player->id)>{{ $player->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm text-stone-600">Status</label>
                    <select name="status" class="{{ $selectClass }}">
                        <option value="">Alle</option>
                        <option value="active" @selected($status === 'active')>Aktiv</option>
                        <option value="inactive" @selected($status === 'inactive')>Inaktiv</option>
                        <option value="missing" @selected($status === 'missing')>Verschollen</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex items-center gap-3">
                    <button type="submit" class="ui small primary button">Filtern</button>
                    <a href="{{ route('heroes.index') }}" class="text-sm text-stone-600 hover:underline">Zurücksetzen</a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                {{-- Mobile: Kartenliste (nur < sm) — UI-38: direkte Navigation statt Modal --}}
                <div class="sm:hidden divide-y divide-stone-200">
                    @forelse ($heroes as $hero)
                        <a href="{{ route('heroes.show', $hero) }}"
                           class="block p-4 hover:bg-black/5 active:bg-black/10 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px] {{ $hero->died ? 'opacity-60' : '' }}">
                            <div class="font-medium text-stone-800">{{ $hero->character_name ?? '—' }}</div>
                            <div class="text-sm text-stone-500 mt-0.5">{{ $hero->player?->full_name ?? '—' }}</div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs text-stone-500">
                                @if ($hero->classes->isNotEmpty())
                                    <span>{{ $hero->classes->pluck('name')->implode(', ') }}</span>
                                @endif
                                <span>{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</span>
                                @if ($hero->died)
                                    <span class="text-red-700 font-medium">verschollen</span>
                                @else
                                    <span>{{ $hero->active ? 'aktiv' : 'inaktiv' }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center">
                            <p class="font-medium text-stone-700 mb-1">Noch keine Helden erfasst.</p>
                            <p class="text-sm text-stone-500 mb-4">
                                Bevor ein Held angelegt werden kann, muss ein
                                <a href="{{ route('players.index') }}" class="text-waldritter hover:underline">Spieler</a>
                                vorhanden sein.
                            </p>
                            @can('heldenregister.edit')
                                <a href="{{ route('heroes.create') }}" class="ui small primary button">Neuen Helden anlegen</a>
                            @endcan
                        </div>
                    @endforelse
                </div>

                {{-- Desktop: Tabelle (ab sm) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Spieler</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Charakter</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase">EP gesamt</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase">EP verfügbar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Klassen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Aktiv</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 text-stone-800">
                            @forelse ($heroes as $hero)
                                <tr data-modal-url="{{ route('heroes.show', $hero) }}"
                                    role="button" tabindex="0"
                                    aria-label="Held {{ $hero->character_name ?? 'unbenannt' }} öffnen"
                                    class="cursor-pointer hover:bg-black/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px] {{ $hero->died ? 'opacity-60' : '' }}">
                                    <td class="px-6 py-4">{{ $hero->player?->full_name ?? '—' }}</td>
                                    <td class="px-6 py-4 font-medium">{{ $hero->character_name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right">{{ number_format($hero->ep_total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right">{{ number_format($hero->ep_balance, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4">{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if ($hero->died)
                                            <span class="text-red-700">verschollen</span>
                                        @else
                                            {{ $hero->active ? 'ja' : 'nein' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center">
                                        <p class="font-medium text-stone-700 mb-1">Noch keine Helden erfasst.</p>
                                        <p class="text-sm text-stone-500 mb-4">
                                            Bevor ein Held angelegt werden kann, muss ein
                                            <a href="{{ route('players.index') }}" class="text-waldritter hover:underline">Spieler</a>
                                            vorhanden sein.
                                        </p>
                                        @can('heldenregister.edit')
                                            <a href="{{ route('heroes.create') }}" class="ui small primary button">Neuen Helden anlegen</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="mt-4">
                {{ $heroes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
