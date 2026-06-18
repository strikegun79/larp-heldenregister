<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Teilnahme-Rollen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.event-roles.create') }}" data-modal-url="{{ route('admin.event-roles.create') }}" class="ui primary button">Neue Rolle</a>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Bezeichnung</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Anmeldungen</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($roles as $role)
                            <tr>
                                <td class="px-6 py-4">{{ $role->description }}</td>
                                <td class="px-6 py-4">{{ $role->bookings_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.event-roles.edit', $role) }}" data-modal-url="{{ route('admin.event-roles.edit', $role) }}" class="text-indigo-700 hover:underline">Bearbeiten</a>
                                        @if (! $role->bookings_count)
                                            <form method="POST" action="{{ route('admin.event-roles.destroy', $role) }}"
                                                  onsubmit="return confirm('Rolle „{{ $role->description }}“ löschen?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-stone-500">Noch keine Rollen.</td></tr>
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
