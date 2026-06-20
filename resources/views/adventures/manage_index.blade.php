<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Abenteuer verwalten</h2>
            <a href="{{ route('adventures.create') }}"><x-primary-button>Neues Abenteuer</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 mb-6 text-stone-700">
                Verwaltungsansicht – zum Browsen/Anmelden siehe <a href="{{ route('adventures.index') }}" class="text-waldritter hover:underline">Abenteuer</a>.
            </div>

            {{-- Desktop: Fomantic-UI-Tabelle (ab sm), Zeilenklick → Verwaltungsseite --}}
            <div class="hidden sm:block bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-x-auto">
                <table class="ui table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Beginn</th>
                            <th>Status</th>
                            <th>Belegung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adventures as $adventure)
                            <tr data-navigate="{{ route('adventures.manage', $adventure) }}"
                                role="button" tabindex="0"
                                aria-label="Abenteuer {{ $adventure->name }} verwalten"
                                class="cursor-pointer hover:!bg-stone-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600">
                                <td class="font-medium">{{ $adventure->name }}</td>
                                <td>{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                <td>@include('adventures._status_badge', ['status' => $adventure->status])</td>
                                <td>{{ $adventure->confirmed_bookings_count }} / {{ $adventure->max_player }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-stone-500">Noch keine Abenteuer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobil: Kartenliste (bis sm), Klick → Detailseite --}}
            <div class="sm:hidden bg-white/70 border-2 border-[#5a3a22]/40 shadow rounded-lg overflow-hidden">
                @forelse ($adventures as $adventure)
                    <a href="{{ route('adventures.show', $adventure) }}"
                       class="block p-4 border-b border-stone-200 last:border-b-0 hover:bg-stone-50 active:bg-stone-100 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600">
                        <div class="font-medium text-stone-800">{{ $adventure->name }}</div>
                        <div class="text-sm text-stone-500 mt-0.5">
                            {{ optional($adventure->start_at)->format('d.m.Y H:i') }}
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs">
                            @include('adventures._status_badge', ['status' => $adventure->status])
                            <span class="text-stone-500">{{ $adventure->confirmed_bookings_count }}/{{ $adventure->max_player }} Plätze</span>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-stone-500">Noch keine Abenteuer.</div>
                @endforelse
            </div>

            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
            <div class="mt-4">{{ $adventures->links() }}</div>
        </div>
    </div>
</x-app-layout>
