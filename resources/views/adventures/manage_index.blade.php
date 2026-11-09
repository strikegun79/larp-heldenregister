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


            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Beginn</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Belegung</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($adventures as $adventure)
                            <tr>
                                <td class="px-6 py-4">{{ $adventure->name }}</td>
                                <td class="px-6 py-4">{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                <td class="px-6 py-4">@include('adventures._status_badge', ['status' => $adventure->status])</td>
                                <td class="px-6 py-4">{{ $adventure->confirmed_bookings_count }} / {{ $adventure->max_player }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('adventures.manage', $adventure) }}" data-modal-url="{{ route('adventures.manage', $adventure) }}" class="text-indigo-700 hover:underline">Verwalten</a>
                                        @if ($adventure->event_status_id !== \App\Models\EventStatus::CANCELLED && $adventure->canTransitionTo(\App\Models\EventStatus::CANCELLED))
                                            <form method="POST" action="{{ route('adventures.cancel', $adventure) }}"
                                                  onsubmit="return confirm('Event „{{ $adventure->name }}“ wirklich absagen?');">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:underline">Absagen</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-stone-500">Noch keine Abenteuer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
            <div class="mt-4">{{ $adventures->links() }}</div>
        </div>
    </div>
</x-app-layout>
