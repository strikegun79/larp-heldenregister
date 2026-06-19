<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">EP-Buchungsarten</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.ep-transaction-types.create') }}"
                   data-modal-url="{{ route('admin.ep-transaction-types.create') }}"
                   class="ui primary button">Neue Buchungsart</a>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Beschreibung</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Typ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Buchungen</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($types as $type)
                            <tr>
                                <td class="px-6 py-4 font-mono text-sm">{{ $type->id }}</td>
                                <td class="px-6 py-4">{{ $type->description }}</td>
                                <td class="px-6 py-4">
                                    @if ($type->is_credit)
                                        <span class="ui mini green label">Gutschrift</span>
                                    @else
                                        <span class="ui mini orange label">Buchung</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $type->transactions_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.ep-transaction-types.edit', $type) }}"
                                           data-modal-url="{{ route('admin.ep-transaction-types.edit', $type) }}"
                                           class="text-waldritter hover:underline">Bearbeiten</a>
                                        @if (! $type->transactions_count)
                                            <form method="POST" action="{{ route('admin.ep-transaction-types.destroy', $type) }}"
                                                  data-confirm="Buchungsart {{ $type->description }} löschen?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-stone-500">Noch keine Buchungsarten.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
