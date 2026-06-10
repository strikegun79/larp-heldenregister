<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
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
            @if (session('status'))
                <div class="mb-4 rounded bg-green-100 dark:bg-green-900 px-4 py-2 text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @php($selectClass = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500')
            <form method="GET" action="{{ route('heroes.index') }}"
                  class="mb-4 bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
                <div class="lg:col-span-2">
                    <label class="text-sm text-stone-600">Suche (Spieler- oder Charaktername)</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="z. B. Tilix oder Müller" class="{{ $selectClass }}">
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Spieler</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Charakter</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">EP gesamt</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">EP verfügbar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Klassen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktiv</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                        @forelse ($heroes as $hero)
                            <tr data-modal-url="{{ route('heroes.show', $hero) }}" class="cursor-pointer hover:bg-black/5 {{ $hero->died ? 'opacity-60' : '' }}">
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
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Noch keine Helden erfasst.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $heroes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
