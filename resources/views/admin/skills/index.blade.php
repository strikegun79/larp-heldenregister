<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Fertigkeiten</h2>
            <a href="{{ route('admin.skills.create') }}"
               data-modal-url="{{ route('admin.skills.create') }}"
               class="ui primary button">Neue Fertigkeit</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Suche + Klassenfilter --}}
            <form method="GET" action="{{ route('admin.skills.index') }}" class="ui form mb-4 flex flex-wrap items-center gap-3">
                <div class="ui action input">
                    <input type="search" name="q" value="{{ $q }}" placeholder="Name suchen…" style="min-width:200px">
                    <button type="submit" class="ui icon button" aria-label="Suchen"><i class="search icon"></i></button>
                </div>
                <select name="class_id" onchange="this.form.submit()" class="ui dropdown">
                    <option value="">Alle Klassen</option>
                    @foreach ($heroClasses as $class)
                        <option value="{{ $class->id }}" @selected($classId == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
                @if ($q || $classId)
                    <a href="{{ route('admin.skills.index') }}" class="text-sm text-stone-500 hover:underline">Filter zurücksetzen</a>
                @endif
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
                            <tr>
                                <td class="px-2 py-2">
                                    @if ($skill->icon_url)
                                        <img src="{{ $skill->icon_url }}" alt="{{ $skill->name }}" class="w-8 h-8 object-contain rounded">
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
                                           class="ui mini basic button">
                                            {{ $skill->active_heroes_count }}
                                            <i class="users icon ml-1"></i>
                                        </a>
                                    @else
                                        <span class="text-stone-400">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.skills.edit', $skill) }}"
                                           data-modal-url="{{ route('admin.skills.edit', $skill) }}"
                                           class="text-waldritter hover:underline">Bearbeiten</a>
                                        @if (! $skill->heroes_count)
                                            <form method="POST" action="{{ route('admin.skills.destroy', $skill) }}"
                                                  data-confirm="Fertigkeit {{ $skill->name }} löschen?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </div>
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
