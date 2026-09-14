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
                              class="mt-2" data-confirm="Spieler wirklich trotzdem löschen?">
                            @csrf @method('DELETE')
                            <input type="hidden" name="force" value="1">
                            <button type="submit" class="ui mini red button">Trotzdem löschen</button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Suche & Filter in Pergament-Box (A) --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('admin.players.index') }}" class="ui form">
                    {{-- Zeile 1: Suchfeld --}}
                    <div class="flex items-center mb-3">
                        <div class="ui action input w-full sm:w-auto">
                            <input type="search" name="q" value="{{ $q }}" placeholder="Name / Nachname suchen…">
                            <button type="submit" class="ui icon button" aria-label="Suchen"><i class="search icon"></i></button>
                        </div>
                    </div>

                    {{-- Zeile 2: Schnellfilter und Zurücksetzen --}}
                    <div class="flex flex-wrap items-center gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer text-sm select-none">
                            <input type="checkbox" name="hide_adults" value="1" {{ $hideAdults ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>Nur &lt; 18 Jahre</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm select-none">
                            <input type="checkbox" name="no_heroes" value="1" {{ $noHeroes ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>Nur ohne Helden</span>
                        </label>

                        @if ($q !== '' || $hideAdults || $noHeroes)
                            <a href="{{ route('admin.players.index') }}" class="text-sm text-stone-500 hover:underline">Filter zurücksetzen</a>
                        @endif
                    </div>

                    {{-- Legende innerhalb der Filter-Box --}}
                    <div class="flex flex-wrap gap-4 text-xs text-stone-500 pt-2 border-t border-[#5a3a22]/20">
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-3 h-3 rounded-sm bg-green-100 border border-green-400"></span>
                            &lt; 18 Jahre, mit Held
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-3 h-3 rounded-sm bg-amber-100 border border-amber-400"></span>
                            ≥ 18 Jahre
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-3 h-3 rounded-sm bg-red-100 border border-red-400"></span>
                            &lt; 18 Jahre, kein Held
                        </span>
                    </div>
                </form>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-[#5a3a22]/10">
                        <tr>
                            @php
                                /* Sortierspalten-Link-Helper (PLAY-09) */
                                $sortUrl = fn(string $col) => route('admin.players.index', array_filter([
                                    'q'           => $q ?: null,
                                    'hide_adults' => $hideAdults ? '1' : null,
                                    'no_heroes'   => $noHeroes   ? '1' : null,
                                    'sort'        => $col,
                                    'dir'         => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
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
                            @php
                                $age = $player->age;
                                /* Zeilenfarben: kräftigere Töne + farbiger Linksrand (C) */
                                $rowClass = match(true) {
                                    $player->trashed()                                             => 'opacity-50',
                                    $age !== null && $age >= 18                                    => 'bg-amber-100 border-l-4 border-amber-400',
                                    $age !== null && $age < 18 && $player->heroes_count === 0      => 'bg-red-100 border-l-4 border-red-400',
                                    $age !== null && $age < 18                                     => 'bg-green-100 border-l-4 border-green-400',
                                    default                                                        => '',
                                };
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="px-6 py-4" data-label="Name">{{ $player->full_name }}</td>
                                <td class="px-6 py-4" data-label="Alter">{{ $player->age !== null ? $player->age.' J.' : '—' }}</td>
                                <td class="px-6 py-4" data-label="Geschlecht">{{ $player->gender ?? '—' }}</td>
                                <td class="px-6 py-4" data-label="Helden">{{ $player->heroes_count }}</td>
                                <td class="px-6 py-4 text-sm" data-label="Betreut von">{{ $player->users->pluck('name')->implode(', ') ?: '—' }}</td>
                                <td class="px-6 py-4" data-label="Status">{{ $player->trashed() ? 'gelöscht' : ($player->active ? 'aktiv' : 'inaktiv') }}</td>
                                <td class="px-6 py-4 text-sm" data-label="Matrix">
                                    @if ($player->matrixAccount)
                                        <span class="{{ $player->matrixAccount->active ? 'text-green-700' : 'text-stone-500' }}">
                                            {{ $player->matrixAccount->active ? 'aktiv' : 'inaktiv' }}
                                        </span>
                                        <span class="text-stone-400 text-xs block">
                                            {{ $player->matrixAccount->rooms_count }} {{ $player->matrixAccount->rooms_count === 1 ? 'Raum' : 'Räume' }}
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
                                            {{-- Aktions-Links als kleine Buttons (D) --}}
                                            <a href="{{ route('admin.players.edit', $player) }}"
                                               data-modal-url="{{ route('admin.players.edit', $player) }}"
                                               class="ui mini basic button"
                                               title="{{ $player->address_same_as_guardian ? 'Elternanschrift' : 'Abweichende Anschrift' }}">
                                                Anschrift{{ $player->address_same_as_guardian ? '' : ' *' }}
                                            </a>
                                            <a href="{{ route('admin.players.caretakers', $player) }}"
                                               data-modal-url="{{ route('admin.players.caretakers', $player) }}"
                                               class="ui mini basic button">Betreuer</a>
                                            <a href="{{ route('admin.players.matrix.edit', $player) }}"
                                               class="ui mini basic button">Matrix</a>
                                            <form method="POST" action="{{ route('admin.players.destroy', $player->id) }}"
                                                  data-confirm="Spieler löschen?">
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
                </x-mobile.cards-or-table>
            </div>

            <div class="mt-4">{{ $players->links() }}</div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
