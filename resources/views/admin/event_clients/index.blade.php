<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Auftraggeber</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded bg-red-100 px-4 py-2 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('admin.event-clients.create') }}" data-modal-url="{{ route('admin.event-clients.create') }}" class="ui primary button">Neuer Auftraggeber</a>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Events</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($clients as $client)
                            <tr>
                                <td class="px-6 py-4">{{ $client->name }}</td>
                                <td class="px-6 py-4">{{ $client->adventures_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.event-clients.edit', $client) }}" data-modal-url="{{ route('admin.event-clients.edit', $client) }}" class="text-indigo-700 hover:underline">Bearbeiten</a>
                                        @if (! $client->adventures_count)
                                            <form method="POST" action="{{ route('admin.event-clients.destroy', $client) }}"
                                                  onsubmit="return confirm('Auftraggeber „{{ $client->name }}“ löschen?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-stone-500">Noch keine Auftraggeber.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.index') }}" class="inline-block mt-4 text-sm text-stone-600 hover:underline">&larr; Zur Verwaltung</a>
        </div>
    </div>
</x-app-layout>
