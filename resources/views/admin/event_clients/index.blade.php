<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Auftraggeber</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.event-clients.create') }}" data-modal-url="{{ route('admin.event-clients.create') }}" class="ui primary button">Neuer Auftraggeber</a>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Events</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Anforderungen bei Anmeldung</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($clients as $client)
                            <tr>
                                <td class="px-6 py-4" data-label="Name">{{ $client->name }}</td>
                                <td class="px-6 py-4" data-label="Events">{{ $client->adventures_count }}</td>
                                {{-- Zusätzliche Pflicht-Bestätigungen, z. B. Einverständniserklärungen (Issue #14) --}}
                                <td class="px-6 py-4" data-label="Anforderungen">
                                    <ul class="space-y-1">
                                        @foreach ($client->requirements as $requirement)
                                            <li class="flex flex-wrap items-center gap-2 {{ $requirement->active ? '' : 'text-stone-400' }}">
                                                @if ($requirement->pdf_path)
                                                    <a href="{{ route('requirements.pdf', $requirement) }}" target="_blank" rel="noopener"
                                                       title="PDF öffnen" aria-label="PDF zu {{ $requirement->title }} öffnen">
                                                        <i class="file pdf outline icon"></i>
                                                    </a>
                                                @endif
                                                <span>{{ $requirement->title }}</span>
                                                @unless ($requirement->active)
                                                    <span class="ui mini label">inaktiv</span>
                                                @endunless
                                                <a href="{{ route('admin.event-client-requirements.edit', $requirement) }}"
                                                   data-modal-url="{{ route('admin.event-client-requirements.edit', $requirement) }}"
                                                   class="text-waldritter hover:underline text-sm">Bearbeiten</a>
                                                <form method="POST" action="{{ route('admin.event-client-requirements.destroy', $requirement) }}"
                                                      data-confirm="Anforderung „{{ $requirement->title }}” löschen? Wurde ihr bereits zugestimmt, wird sie nur deaktiviert.">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:underline text-sm">Löschen</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('admin.event-clients.requirements.create', $client) }}"
                                       data-modal-url="{{ route('admin.event-clients.requirements.create', $client) }}"
                                       class="text-waldritter hover:underline text-sm">
                                        <i class="plus icon"></i>Anforderung hinzufügen
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.event-clients.edit', $client) }}" data-modal-url="{{ route('admin.event-clients.edit', $client) }}" class="text-waldritter hover:underline">Bearbeiten</a>
                                        @if (! $client->adventures_count)
                                            <form method="POST" action="{{ route('admin.event-clients.destroy', $client) }}"
                                                  data-confirm="Auftraggeber „{{ $client->name }}” löschen?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-4 text-stone-500">Noch keine Auftraggeber.</td></tr>
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
