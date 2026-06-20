<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Abenteuer</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('adventures.calendar') }}">
                    <x-primary-button>Kalender</x-primary-button>
                </a>


                @can('events.edit')
                <a href="{{ route('adventures.manage-index') }}">
                    <x-primary-button>Verwaltung</x-primary-button>
                </a>

                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Suche --}}
            <form method="GET" action="{{ route('adventures.index') }}"
                  class="mb-4 bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-48">
                    <label class="text-sm text-stone-600">Suche</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Abenteuer suchen…"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-amber-600 focus:ring-amber-600">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="ui small primary button">Filtern</button>
                    <a href="{{ route('adventures.index') }}" class="text-sm text-stone-600 hover:underline">Zurücksetzen</a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                {{-- Mobile: Kartenliste (nur < sm) — UI-38: direkte Navigation statt Modal --}}
                <div class="sm:hidden divide-y divide-stone-200">
                    @forelse ($adventures as $adventure)
                        <a href="{{ route('adventures.show', $adventure) }}"
                           class="block p-4 hover:bg-stone-50 active:bg-stone-100 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                            <div class="font-medium text-stone-800">{{ $adventure->name }}</div>
                            <div class="text-sm text-stone-500 mt-0.5">
                                {{ optional($adventure->start_at)->format('d.m.Y H:i') }}
                                @if ($adventure->location)
                                    · {{ $adventure->location->titel }}
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs">
                                <span class="inline-block rounded px-2 py-0.5"
                                      style="background: {{ $adventure->status?->color }}33;">
                                    {{ $adventure->status?->description }}
                                </span>
                                <span class="text-stone-500">
                                    {{ $adventure->confirmed_bookings_count }}/{{ $adventure->max_player }} Plätze
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center">
                            <p class="font-medium text-stone-700 mb-1">Keine Abenteuer geplant.</p>
                            <p class="text-sm text-stone-500 mb-4">Sobald ein Abenteuer veröffentlicht wird, erscheint es hier.</p>
                            @can('events.edit')
                                <a href="{{ route('adventures.manage-index') }}" class="ui small primary button">Zur Verwaltung</a>
                            @endcan
                        </div>
                    @endforelse
                </div>

                {{-- Desktop: Tabelle (ab sm) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Abenteuer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Beginn</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Ort</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Plätze</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 text-stone-800">
                            @forelse ($adventures as $adventure)
                                <tr data-modal-url="{{ route('adventures.show', $adventure) }}"
                                    role="button" tabindex="0"
                                    aria-label="Abenteuer {{ $adventure->name }} öffnen"
                                    class="cursor-pointer hover:bg-stone-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                                    <td class="px-6 py-4 font-medium text-stone-800">{{ $adventure->name }}</td>
                                    <td class="px-6 py-4">{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                    <td class="px-6 py-4">{{ $adventure->location?->titel ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block rounded px-2 py-1 text-xs"
                                              style="background: {{ $adventure->status?->color }}33;">
                                            {{ $adventure->status?->description }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ $adventure->confirmed_bookings_count }} / {{ $adventure->max_player }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center">
                                        <p class="font-medium text-stone-700 mb-1">Keine Abenteuer geplant.</p>
                                        <p class="text-sm text-stone-500 mb-4">Sobald ein Abenteuer veröffentlicht wird, erscheint es hier.</p>
                                        @can('events.edit')
                                            <a href="{{ route('adventures.manage-index') }}" class="ui small primary button">Zur Verwaltung</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="mt-4">{{ $adventures->links() }}</div>
        </div>
    </div>

    @push('scripts')
    <script>
        {{-- ARCH-001: In DOMContentLoaded wrappen, damit heldenregister.js (Modul, deferred)
             vor diesem Inline-Skript ausgeführt wurde und loadModalContent verfügbar ist. --}}
        document.addEventListener('DOMContentLoaded', function () {
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
