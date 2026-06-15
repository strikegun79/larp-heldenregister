<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Alle Spieler</h2>
            <a href="{{ route('admin.players.export') }}" class="ui small button" target="_blank" rel="noopener">Export (CSV)</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Alter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Geschlecht</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Helden</th>
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
                                        <a href="{{ route('admin.players.caretakers', $player) }}" data-modal-url="{{ route('admin.players.caretakers', $player) }}" class="text-indigo-700 hover:underline">Betreuer</a>
                                        <a href="{{ route('admin.players.matrix.edit', $player) }}" class="text-indigo-700 hover:underline">Matrix</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $players->links() }}</div>
            <a href="{{ route('admin.index') }}" class="inline-block mt-4 text-sm text-stone-600 hover:underline">&larr; Zur Verwaltung</a>
        </div>
    </div>
</x-app-layout>
