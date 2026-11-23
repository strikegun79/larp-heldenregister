<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Fertigkeiten</h2>
            <a href="{{ route('admin.skills.create') }}"
               data-inspector-new="{{ route('admin.skills.create') }}"
               class="ui primary button">Neue Fertigkeit</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Suche + Klassenfilter --}}
            <form method="GET" action="{{ route('admin.skills.index') }}" class="ui form mb-4">
                <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">
                    <div class="ui action input w-full sm:w-auto">
                        <input type="search" name="q" value="{{ $q }}" placeholder="Name suchen…">
                        <button type="submit" class="ui icon button" aria-label="Suchen"><i class="search icon"></i></button>
                    </div>
                    <select name="class_id" onchange="this.form.submit()" class="ui dropdown w-full sm:w-auto">
                        <option value="">Alle Klassen</option>
                        @foreach ($heroClasses as $class)
                            <option value="{{ $class->id }}" @selected($classId == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @if ($q || $classId)
                        <a href="{{ route('admin.skills.index') }}" class="text-sm text-stone-500 hover:underline shrink-0">Filter zurücksetzen</a>
                    @endif
                </div>
            </form>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Masterclass</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Lvl</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">EP</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Farbe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Klassen</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Helden</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($skills as $skill)
                            <tr data-inspector-url="{{ route('admin.skills.edit', $skill) }}?inspector=1" tabindex="0"
                                aria-label="Fertigkeit {{ $skill->name }} bearbeiten">
                                <td class="px-2 py-2">
                                    @if ($skill->icon_url)
                                        <img src="{{ $skill->icon_url }}" alt="" class="w-8 h-8 object-contain rounded" loading="lazy">
                                    @else
                                        <span class="inline-block w-8 h-8"></span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium" data-label="Name">{{ $skill->name }}</td>
                                <td class="px-4 py-3 text-sm" data-label="Masterclass">{{ $skill->heroClass?->name ?? '—' }}</td>
                                <td class="px-4 py-3" data-label="Lvl">{{ $skill->level }}</td>
                                <td class="px-4 py-3" data-label="EP">{{ $skill->ep_costs }}</td>
                                <td class="px-4 py-3" data-label="Farbe">
                                    @if ($skill->perlColor)
                                        <span style="display:inline-block;width:1rem;height:1rem;border-radius:50%;border:1px solid #ccc;background:{{ $skill->perlColor->code }};"
                                              title="{{ $skill->perlColor->name }}"></span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm" data-label="Klassen">{{ $skill->classes_count }}</td>
                                <td class="px-4 py-3 text-sm" data-label="Helden">
                                    @if ($skill->active_heroes_count > 0)
                                        <a href="{{ route('admin.skills.heroes', $skill) }}"
                                           data-modal-url="{{ route('admin.skills.heroes', $skill) }}"
                                           class="wr-btn wr-btn--secondary wr-btn--small"
                                           aria-label="{{ $skill->active_heroes_count }} Helden mit {{ $skill->name }} anzeigen">
                                            {{ $skill->active_heroes_count }}
                                            <i class="users icon m-0" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <span class="text-stone-400">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{-- Bearbeiten und Löschen im Inspektor (Zeilenklick / Enter) --}}
                                    <i class="chevron right icon text-wr-ink-muted m-0" aria-hidden="true"></i>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-4 text-stone-500">Keine Fertigkeiten gefunden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </x-mobile.cards-or-table>
            </div>

            <div class="mt-4">{{ $skills->links() }}</div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
