<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Abenteuer</h2>
            @can('manage-events')
                <a href="{{ route('adventures.create') }}"><x-primary-button>Neues Abenteuer</x-primary-button></a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-100 dark:bg-green-900 px-4 py-2 text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abenteuer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beginn</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ort</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plätze</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                        @forelse ($adventures as $adventure)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('adventures.show', $adventure) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $adventure->name }}</a>
                                </td>
                                <td class="px-6 py-4">{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                <td class="px-6 py-4">{{ $adventure->location?->titel ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block rounded px-2 py-1 text-xs" style="background: {{ $adventure->status?->color }}33;">
                                        {{ $adventure->status?->description }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $adventure->confirmed_bookings_count }} / {{ $adventure->max_player }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Noch keine Abenteuer erfasst.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $adventures->links() }}</div>
        </div>
    </div>
</x-app-layout>
