<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Abenteuer</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Aktions-Buttons unter dem Titel --}}
            <div class="mb-4 flex flex-wrap gap-2">
                <a href="{{ route('adventures.calendar') }}" class="ui small button">
                    <i class="calendar icon" aria-hidden="true"></i> Kalender
                </a>
                @can('events.edit')
                <a href="{{ route('adventures.manage-index') }}" class="ui small primary button">
                    <i class="cogs icon" aria-hidden="true"></i> Verwaltung
                </a>
                @endcan
            </div>

            {{-- Suche --}}
            <form method="GET" action="{{ route('adventures.index') }}"
                  class="mb-3 bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="flex-1 min-w-48">
                    <label for="adventures-search" class="text-sm text-stone-600">Suche</label>
                    <input id="adventures-search" type="text" name="q" value="{{ $q }}"
                           placeholder="Abenteuer suchen…"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-amber-600 focus:ring-amber-600">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="ui small primary button">Filtern</button>
                    <a href="{{ route('adventures.index', ['sort' => $sort]) }}" class="text-sm text-stone-600 hover:underline">Zurücksetzen</a>
                </div>
            </form>

            {{-- Sortiersteuerung: einheitliches Fomantic-Dropdown für alle Breakpoints --}}
            <div class="mb-4">
                <label for="adventure-sort" class="sr-only">Ansicht der Abenteuerliste wählen</label>
                <div class="ui selection dropdown" id="adventure-sort">
                    <i class="dropdown icon"></i>
                    <div class="text">
                        @if ($sort === 'grouped') <i class="th list icon"></i> Gruppiert
                        @elseif ($sort === 'asc')  <i class="sort amount up icon"></i> Nächstes zuerst
                        @else                       <i class="sort amount down icon"></i> Letztes zuerst
                        @endif
                    </div>
                    <div class="menu">
                        <div class="item {{ $sort === 'grouped' ? 'active selected' : '' }}"
                             data-value="{{ route('adventures.index', array_merge(request()->except('sort', 'page'), ['sort' => 'grouped'])) }}">
                            <i class="th list icon"></i> Gruppiert
                        </div>
                        <div class="item {{ $sort === 'asc' ? 'active selected' : '' }}"
                             data-value="{{ route('adventures.index', array_merge(request()->except('sort', 'page'), ['sort' => 'asc'])) }}">
                            <i class="sort amount up icon"></i> Nächstes zuerst
                        </div>
                        <div class="item {{ $sort === 'desc' ? 'active selected' : '' }}"
                             data-value="{{ route('adventures.index', array_merge(request()->except('sort', 'page'), ['sort' => 'desc'])) }}">
                            <i class="sort amount down icon"></i> Letztes zuerst
                        </div>
                    </div>
                </div>
            </div>

            @if ($sort === 'grouped')
                {{-- ── Gruppenansicht ───────────────────────────────────────── --}}
                @if ($grouped->isEmpty())
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        @include('adventures._empty_state')
                    </div>
                @else
                    @foreach ($grouped as $statusId => $group)
                        @php $status = $group->first()->status; @endphp
                        <div class="mb-5 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            {{-- Gruppen-Header --}}
                            <div class="px-4 py-2 border-b border-stone-200 flex items-center gap-2"
                                 style="background: {{ $status?->color }}22;">
                                <span class="inline-block w-3 h-3 rounded-full flex-shrink-0"
                                      aria-hidden="true"
                                      style="background: {{ $status?->color }};"></span>
                                <h3 class="text-sm font-semibold text-stone-700 uppercase tracking-wide">
                                    {{ $status?->description }}
                                    <span class="ml-1 font-normal normal-case text-stone-400">({{ $group->count() }})</span>
                                </h3>
                            </div>

                            {{-- Mobile: Kartenliste --}}
                            <div class="sm:hidden divide-y divide-stone-200">
                                @foreach ($group as $adventure)
                                    @php $free = $adventure->max_player - $adventure->confirmed_bookings_count; @endphp
                                    <a href="{{ route('adventures.show', $adventure) }}"
                                       class="block p-4 hover:bg-stone-50 active:bg-stone-100 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                                        <div class="font-medium text-stone-800">{{ $adventure->name }}</div>
                                        <div class="text-sm text-stone-500 mt-0.5">
                                            {{ optional($adventure->start_at)->format('d.m.Y H:i') }}
                                            @if ($adventure->location)
                                                · {{ $adventure->location->titel }}
                                            @endif
                                        </div>
                                        @if ($adventure->min_age || $adventure->max_age)
                                            <div class="text-xs text-stone-400 mt-0.5">Alter: {{ $adventure->age_range_label }}</div>
                                        @endif
                                        <div class="mt-1 text-xs">
                                            @if ($free > 0)
                                                <span class="text-green-700">Noch {{ $free }} frei</span>
                                            @else
                                                <span class="text-red-600 font-medium">Ausgebucht</span>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                            {{-- Desktop: Tabelle --}}
                            <div class="hidden sm:block overflow-x-auto">
                                <table class="min-w-full divide-y divide-stone-200">
                                    <thead hidden>
                                        <tr>
                                            <th>Abenteuer</th>
                                            <th>Beginn</th>
                                            <th>Ort</th>
                                            <th>Alter</th>
                                            <th>Plätze</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stone-200 text-stone-800">
                                        @foreach ($group as $adventure)
                                            @php $free = $adventure->max_player - $adventure->confirmed_bookings_count; @endphp
                                            <tr data-modal-url="{{ route('adventures.show', $adventure) }}"
                                                role="button" tabindex="0"
                                                aria-label="Abenteuer {{ $adventure->name }} öffnen"
                                                class="cursor-pointer hover:bg-stone-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                                                <td class="px-6 py-4 font-medium text-stone-800">{{ $adventure->name }}</td>
                                                <td class="px-6 py-4">{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                                <td class="px-6 py-4">{{ $adventure->location?->titel ?? '—' }}</td>
                                                <td class="px-6 py-4 text-sm text-stone-500">{{ $adventure->age_range_label }}</td>
                                                <td class="px-6 py-4 text-sm">
                                                    @if ($free > 0)
                                                        <span class="text-green-700">Noch {{ $free }} frei</span>
                                                    @else
                                                        <span class="text-red-600 font-medium">Ausgebucht</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif

            @else
                {{-- ── Datum-Sortierung (flache Liste) ─────────────────────── --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                    {{-- Mobile: Kartenliste --}}
                    <div class="sm:hidden divide-y divide-stone-200">
                        @forelse ($adventures as $adventure)
                            @php $free = $adventure->max_player - $adventure->confirmed_bookings_count; @endphp
                            <a href="{{ route('adventures.show', $adventure) }}"
                               class="block p-4 hover:bg-stone-50 active:bg-stone-100 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                                <div class="font-medium text-stone-800">{{ $adventure->name }}</div>
                                <div class="text-sm text-stone-500 mt-0.5">
                                    {{ optional($adventure->start_at)->format('d.m.Y H:i') }}
                                    @if ($adventure->location)
                                        · {{ $adventure->location->titel }}
                                    @endif
                                </div>
                                @if ($adventure->min_age || $adventure->max_age)
                                    <div class="text-xs text-stone-400 mt-0.5">Alter: {{ $adventure->age_range_label }}</div>
                                @endif
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs">
                                    <span class="inline-block rounded px-2 py-0.5"
                                          style="background: {{ $adventure->status?->color }}33;">
                                        {{ $adventure->status?->description }}
                                    </span>
                                    @if ($free > 0)
                                        <span class="text-green-700">Noch {{ $free }} frei</span>
                                    @else
                                        <span class="text-red-600 font-medium">Ausgebucht</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            @include('adventures._empty_state')
                        @endforelse
                    </div>

                    {{-- Desktop: Tabelle --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200">
                            <thead class="bg-stone-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Abenteuer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Beginn</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Ort</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Alter</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Plätze</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 text-stone-800">
                                @forelse ($adventures as $adventure)
                                    @php $free = $adventure->max_player - $adventure->confirmed_bookings_count; @endphp
                                    <tr data-modal-url="{{ route('adventures.show', $adventure) }}"
                                        role="button" tabindex="0"
                                        aria-label="Abenteuer {{ $adventure->name }} öffnen"
                                        class="cursor-pointer hover:bg-stone-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                                        <td class="px-6 py-4 font-medium text-stone-800">{{ $adventure->name }}</td>
                                        <td class="px-6 py-4">{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                        <td class="px-6 py-4">{{ $adventure->location?->titel ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-stone-500">{{ $adventure->age_range_label }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-block rounded px-2 py-1 text-xs"
                                                  style="background: {{ $adventure->status?->color }}33;">
                                                {{ $adventure->status?->description }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            @if ($free > 0)
                                                <span class="text-green-700">Noch {{ $free }} frei</span>
                                            @else
                                                <span class="text-red-600 font-medium">Ausgebucht</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center">
                                            @include('adventures._empty_state')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="mt-4">{{ $adventures->links() }}</div>
            @endif

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Sortier-Dropdown: navigiert bei Auswahl zur gewählten URL.
            $('#adventure-sort').dropdown({
                onChange: function (value) {
                    if (value) window.location.href = value;
                }
            });

            // Modal öffnen via ?open=<id>.
            const open = new URLSearchParams(window.location.search).get('open');
            if (!open) return;
            history.replaceState({}, '', '{{ route('adventures.index') }}');
            window.appModalUrl = '{{ url('adventures') }}/' + open;
            $('#app-modal').modal({ autofocus: false, observeChanges: true }).modal('show');
            loadModalContent(window.appModalUrl);
        });
    </script>
    @endpush
</x-app-layout>
