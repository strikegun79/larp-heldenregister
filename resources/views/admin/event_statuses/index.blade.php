<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Event-Status</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Farbe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Bezeichnung</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Events</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($statuses as $status)
                            <tr>
                                <td class="px-6 py-4 font-mono text-stone-500">{{ $status->id }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block w-6 h-6 rounded border border-stone-300"
                                          style="background:{{ $status->color }}"
                                          title="{{ $status->color }}"></span>
                                </td>
                                <td class="px-6 py-4">{{ $status->description }}</td>
                                <td class="px-6 py-4">{{ $status->adventures_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.event-statuses.edit', $status) }}"
                                           data-modal-url="{{ route('admin.event-statuses.edit', $status) }}"
                                           class="text-indigo-700 hover:underline">Bearbeiten</a>
                                        @if ($status->adventures_count === 0)
                                            <form method="POST"
                                                  action="{{ route('admin.event-statuses.destroy', $status) }}"
                                                  onsubmit="return confirm('Status „{{ $status->description }}" löschen?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @else
                                            <span class="text-stone-400 text-sm">in Verwendung</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-stone-500">Keine Status vorhanden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
