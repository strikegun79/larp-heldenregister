<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Fertigkeiten-Katalog</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Klassen-Filter --}}
            <form method="GET" action="{{ route('skills.catalog') }}"
                  class="mb-6 bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-sm text-stone-600">Klasse</label>
                    <select name="class_id"
                            class="mt-1 block border-gray-300 rounded-md shadow-sm text-sm focus:border-amber-600 focus:ring-amber-600">
                        <option value="">– alle Klassen –</option>
                        @foreach ($allClasses as $cls)
                            <option value="{{ $cls->id }}" @selected($classId == $cls->id)>{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="ui small primary button">Filtern</button>
                    <a href="{{ route('skills.catalog') }}" class="text-sm text-stone-600 hover:underline">Zurücksetzen</a>
                </div>
            </form>

            @forelse ($heroClasses as $class)
                <div class="mb-8">
                    <h3 class="font-uncial text-lg text-waldritter mb-3 border-b-2 border-[#5a3a22]/30 pb-1">
                        {{ $class->name }}
                    </h3>

                    <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-black/5">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Fertigkeit</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase w-16">Level</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase w-20">EP</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase w-24">Perle</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase w-24">Helden</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-stone-800">
                                @foreach ($class->skills as $skill)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                @if ($skill->icon_url)
                                                    <img src="{{ $skill->icon_url }}" alt="{{ $skill->name }}"
                                                         class="w-8 h-8 object-contain rounded shrink-0">
                                                @endif
                                                <div>
                                                    <div class="font-medium">{{ $skill->name }}</div>
                                                    @if ($skill->description)
                                                        <div class="text-stone-500 text-xs mt-0.5">{{ $skill->description }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-stone-600">
                                            {{ $skill->level ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-stone-600">
                                            {{ $skill->ep_costs ? $skill->ep_costs.' EP' : '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($skill->perlColor)
                                                <span class="inline-flex items-center gap-1.5">
                                                    <span class="inline-block w-3.5 h-3.5 rounded-full border border-stone-300 shrink-0"
                                                          style="background:{{ $skill->perlColor->code }}"
                                                          title="{{ $skill->perlColor->name }}"></span>
                                                    <span class="text-xs text-stone-500">{{ $skill->perlColor->name }}</span>
                                                </span>
                                            @else
                                                <span class="text-stone-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($skill->active_heroes_count > 0)
                                                <a href="{{ route('skills.catalog.heroes', $skill) }}"
                                                   data-modal-url="{{ route('skills.catalog.heroes', $skill) }}"
                                                   class="ui mini basic button">
                                                    {{ $skill->active_heroes_count }}
                                                    <i class="users icon ml-1"></i>
                                                </a>
                                            @else
                                                <span class="text-stone-400">0</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-stone-500 text-center py-12">
                    Für die gewählte Klasse sind keine Fertigkeiten hinterlegt.
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
