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

            <div class="mb-4 flex gap-4 text-sm">
                <a href="{{ route('heroes.index') }}" class="{{ ! $status ? 'font-semibold text-waldritter' : 'text-stone-600 hover:underline' }}">Alle</a>
                <a href="{{ route('heroes.index', ['status' => 'present']) }}" class="{{ $status === 'present' ? 'font-semibold text-waldritter' : 'text-stone-600 hover:underline' }}">Aktive</a>
                <a href="{{ route('heroes.index', ['status' => 'missing']) }}" class="{{ $status === 'missing' ? 'font-semibold text-waldritter' : 'text-stone-600 hover:underline' }}">Verschollene</a>
            </div>

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
