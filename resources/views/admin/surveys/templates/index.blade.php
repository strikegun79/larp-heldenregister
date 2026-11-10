<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Umfrage-Vorlagen</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.surveys.index') }}" class="ui button">← Umfragen</a>
                <a href="{{ route('admin.surveys.templates.create') }}" class="ui primary button">
                    + Neue Vorlage
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="ui success message mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="ui error message mb-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Zielgruppe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Fragen</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Erstellt von</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse($templates as $template)
                            <tr>
                                <td class="px-4 py-3 font-medium" data-label="Name">{{ $template->name }}</td>
                                <td class="px-4 py-3 text-sm text-stone-500" data-label="Zielgruppe">
                                    {{ $template->target_group_label }}
                                </td>
                                <td class="px-4 py-3 text-sm" data-label="Fragen">{{ $template->questions_count }}</td>
                                <td class="px-4 py-3 text-sm text-stone-500" data-label="Erstellt von">
                                    {{ $template->creator?->name ?? '–' }}
                                </td>
                                <td class="px-4 py-3" data-label="Status">
                                    <span class="ui {{ $template->active ? 'green' : 'grey' }} label">
                                        {{ $template->active ? 'Aktiv' : 'Inaktiv' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.surveys.templates.edit', $template) }}"
                                       class="ui small button">Bearbeiten</a>
                                    <form method="POST"
                                          action="{{ route('admin.surveys.templates.destroy', $template) }}"
                                          class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="ui small red button"
                                                onclick="return confirm('Vorlage wirklich löschen?')">
                                            Löschen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-400">
                                    Noch keine Vorlagen vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </x-mobile.cards-or-table>
            </div>

        </div>
    </div>
</x-app-layout>
