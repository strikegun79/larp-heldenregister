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


            <div class=”bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden”>
                <x-mobile.cards-or-table>
                    <table class=”min-w-full divide-y divide-stone-200”>
                        <thead class=”bg-black/5”>
                            <tr>
                                <th class=”px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase”>Name</th>
                                <th class=”px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase”>Beginn</th>
                                <th class=”px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase”>Status</th>
                                <th class=”px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase”>Belegung</th>
                            </tr>
                        </thead>
                        <tbody class=”divide-y divide-stone-200 text-stone-800”>
                            @forelse ($adventures as $adventure)
                                <tr data-navigate=”{{ route('adventures.manage', $adventure) }}”
                                    role=”button” tabindex=”0”
                                    aria-label=”Abenteuer {{ $adventure->name }} verwalten”
                                    class=”cursor-pointer hover:bg-stone-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]”>
                                    <td data-label=”Name” class=”px-6 py-4 font-medium”>{{ $adventure->name }}</td>
                                    <td data-label=”Beginn” class=”px-6 py-4”>{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                    <td data-label=”Status” class=”px-6 py-4”>@include('adventures._status_badge', ['status' => $adventure->status])</td>
                                    <td data-label=”Belegung” class=”px-6 py-4”>{{ $adventure->confirmed_bookings_count }} / {{ $adventure->max_player }}</td>
                                </tr>
                            @empty
                                <tr><td colspan=”4” class=”px-6 py-4 text-stone-500”>Noch keine Abenteuer.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-mobile.cards-or-table>
            </div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
            <div class="mt-4">{{ $adventures->links() }}</div>
        </div>
    </div>
</x-app-layout>
