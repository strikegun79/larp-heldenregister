<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Alle Spieler</h2>
            <a href="{{ route('admin.players.export') }}" class="ui small button" target="_blank" rel="noopener">Export (CSV)</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('warning'))
                <div class="ui warning message mb-4">
                    <div class="header">Hinweis vor dem Löschen</div>
                    <p>{{ session('warning') }}</p>
                    @if (session('force_delete_id'))
                        <form method="POST" action="{{ route('admin.players.destroy', session('force_delete_id')) }}"
                              class="mt-2" onsubmit="return confirm('Spieler trotzdem loeschen?');">
                            @csrf @method('DELETE')
                            <input type="hidden" name="force" value="1">
                            <button type="submit" class="ui mini red button">Trotzdem löschen</button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Suche (PLAY-09) --}}
            <form method="GET" action="{{ route('admin.players.index') }}" class="ui form mb-4">
                <div class="flex items-center gap-3">
                    <div class="ui action input">
                        <input type="search" name="q" value="{{ $q }}" placeholder="Name / Nachname suchen…" style="min-width:220px">
                        <button type="submit" class="ui icon button" aria-label="Suchen"><i class="search icon"></i></button>
                    </div>
                    @if ($q !== '')
                        <a href="{{ route('admin.players.index') }}" class="text-sm text-stone-500 hover:underline">Filter zurücksetzen</a>
                    @endif
                </div>
            </form>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            @php
                                /* Sortierspalten-Link-Helper (PLAY-09) */
                                $sortUrl = fn(string $col) => route('admin.players.index', array_filter([
                                    'q' => $q ?: null,
                                    'sort' => $col,
                                    'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
                                ]));
                                $sortIcon = fn(string $col) => $sort === $col
                                    ? ($dir === 'asc' ? ' ↑' : ' ↓')
                                    : '';
                            @endphp
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">
                                <a href="{{ $sortUrl('name') }}" class="hover:text-stone-800">Name{{ $sortIcon('name') }}</a>
                                /
                                <a href="{{ $sortUrl('lastname') }}" class="hover:text-stone-800">Nachname{{ $sortIcon('lastname') }}</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">
                                <a href="{{ $sortUrl('dayofbirth') }}" class="hover:text-stone-800">Alter{{ $sortIcon('dayofbirth') }}</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Geschlecht</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">
                                <a href="{{ $sortUrl('heroes_count') }}" class="hover:text-stone-800">Helden{{ $sortIcon('heroes_count') }}</a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Betreut von</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Matrix</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @foreach ($players as $player)
                            <tr class="{{ $player->trashed() ? 'opacity-50' : '' }}">
                                <td class="px-6 py-4">{{ $player->full_name }}</td>
                                <td class="px-6 py-4">{{ $player->age !== null ? $player->age.' J.' : '—' }}</td>
                                <td class="px-6 py-4">{{ $player->gender ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $player->heroes_count }}</td>
                                <td class="px-6 py-4 text-sm">{{ $player->users->pluck('name')->implode(', ') ?: '—' }}</td>
                                <td class="px-6 py-4">{{ $player->trashed() ? 'gelöscht' : ($player->active ? 'aktiv' : 'inaktiv') }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($player->matrixAccount)
                                        <span class="{{ $player->matrixAccount->active ? 'text-green-700' : 'text-stone-500' }}">
                                            {{ $player->matrixAccount->active ? 'aktiv' : 'inaktiv' }}
                                        </span>
                                    @else
                                        <span class="text-stone-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @if ($player->trashed())
                                            <form method="POST" action="{{ route('admin.players.restore', $player->id) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ui mini green button"
                                                        data-tooltip="Wiederherstellen" data-position="top center">
                                                    <i class="undo icon"></i> Wiederherstellen
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.players.edit', $player) }}"
                                               data-modal-url="{{ route('admin.players.edit', $player) }}"
                                               class="text-indigo-700 hover:underline"
                                               title="{{ $player->address_same_as_guardian ? 'Elternanschrift' : 'Abweichende Anschrift' }}">
                                                Anschrift{{ $player->address_same_as_guardian ? '' : ' *' }}
                                            </a>
                                            <a href="{{ route('admin.players.caretakers', $player) }}"
                                               data-modal-url="{{ route('admin.players.caretakers', $player) }}"
                                               class="text-indigo-700 hover:underline">Betreuer</a>
                                            <a href="{{ route('admin.players.matrix.edit', $player) }}"
                                               class="text-indigo-700 hover:underline">Matrix</a>
                                            <form method="POST" action="{{ route('admin.players.destroy', $player->id) }}"
                                                  onsubmit="return confirm('Spieler loeschen?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="ui mini red icon button"
                                                        data-tooltip="Löschen" data-position="top center">
                                                    <i class="trash icon"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $players->links() }}</div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
