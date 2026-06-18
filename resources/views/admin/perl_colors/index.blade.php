<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Perlenfarben</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.perl-colors.create') }}"
                   data-modal-url="{{ route('admin.perl-colors.create') }}"
                   class="ui primary button">Neue Perlenfarbe</a>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Farbe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Fertigkeiten</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($colors as $color)
                            <tr>
                                <td class="px-6 py-4">
                                    <span style="display:inline-block;width:1.5rem;height:1.5rem;border-radius:50%;border:1px solid #ccc;background:{{ $color->code }};"></span>
                                </td>
                                <td class="px-6 py-4">{{ $color->name }}</td>
                                <td class="px-6 py-4 font-mono text-sm">{{ $color->code }}</td>
                                <td class="px-6 py-4">{{ $color->skills_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.perl-colors.edit', $color) }}"
                                           data-modal-url="{{ route('admin.perl-colors.edit', $color) }}"
                                           class="text-indigo-700 hover:underline">Bearbeiten</a>
                                        @if (! $color->skills_count)
                                            <form method="POST" action="{{ route('admin.perl-colors.destroy', $color) }}"
                                                  onsubmit="return confirm('Perlenfarbe \"{{ $color->name }}\" loeschen?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-stone-500">Noch keine Perlenfarben.</td></tr>
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
