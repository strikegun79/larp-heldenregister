<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Veranstaltungsorte</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.locations.create') }}" data-modal-url="{{ route('admin.locations.create') }}" class="ui primary button">Neuer Ort</a>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Titel</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">PLZ / Stadt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Adresse</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Events</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($locations as $location)
                            <tr>
                                <td class="px-6 py-4" data-label="Titel">{{ $location->titel }}</td>
                                <td class="px-6 py-4" data-label="PLZ / Stadt">{{ trim($location->plz.' '.$location->city) ?: '—' }}</td>
                                <td class="px-6 py-4" data-label="Adresse">{{ $location->address ?: '—' }}</td>
                                <td class="px-6 py-4" data-label="Events">{{ $location->adventures_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.locations.edit', $location) }}" data-modal-url="{{ route('admin.locations.edit', $location) }}" class="text-waldritter hover:underline">Bearbeiten</a>
                                        <form method="POST" action="{{ route('admin.locations.destroy', $location) }}"
                                              data-confirm="Ort „{{ $location->titel }}” löschen?{{ $location->adventures_count ? ' '.$location->adventures_count.' Abenteuer verlieren den Ort.' : '' }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-stone-500">Noch keine Orte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </x-mobile.cards-or-table>
            </div>

            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
