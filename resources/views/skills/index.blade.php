<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Fertigkeiten-Katalog</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Klassen-Filter --}}
            <form method="GET" action="{{ route('skills.catalog') }}" class="mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-stone-500 mb-1">Klasse</label>
                    <select name="class_id" onchange="this.form.submit()"
                            class="border border-stone-300 rounded px-3 py-1.5 text-sm bg-white">
                        <option value="">– alle Klassen –</option>
                        @foreach ($allClasses as $cls)
                            <option value="{{ $cls->id }}" @selected($classId == $cls->id)>{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($classId)
                    <a href="{{ route('skills.catalog') }}" class="ui button text-sm">Alle anzeigen</a>
                @endif
            </form>

            @forelse ($heroClasses as $class)
                <div class="mb-8">
                    <h3 class="font-uncial text-lg text-waldritter mb-3 border-b-2 border-[#5a3a22]/30 pb-1">
                        {{ $class->name }}
                    </h3>

                    <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-black/5">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Fertigkeit</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase w-16">Level</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase w-20">EP</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase w-24">Perle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-stone-800">
                                @foreach ($class->skills as $skill)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3">
                                            <div class="font-medium">{{ $skill->name }}</div>
                                            @if ($skill->description)
                                                <div class="text-stone-500 text-xs mt-0.5">{{ $skill->description }}</div>
                                            @endif
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
