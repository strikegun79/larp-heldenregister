<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-xl text-waldritter">Heldenausweise</h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Generator --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-6 shadow">
            <h3 class="font-uncial text-waldritter text-lg mb-4">Ausweis-PDF generieren</h3>
            <p class="text-stone-600 text-sm mb-4">
                Gib die Anzahl der zu druckenden Ausweise ein. Das System generiert Zufallscodes,
                speichert sie im Pool und erstellt eine PDF (7,52&nbsp;cm &times; 10&nbsp;cm,
                3&times;2 auf A4 quer) zum Ausdrucken.
            </p>
            <form method="POST" action="{{ route('admin.id-cards.generate') }}"
                  class="ui form" target="_blank">
                @csrf
                <div class="flex flex-wrap items-end gap-3">
                    <div class="field !mb-0">
                        <label>Anzahl Ausweise</label>
                        <input type="number" name="count" min="1" max="200"
                               value="{{ old('count', 6) }}"
                               class="w-28" required>
                    </div>
                    <button type="submit" class="ui primary button">
                        <i class="file pdf icon"></i> PDF generieren &amp; anzeigen
                    </button>
                </div>
                @error('count')
                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                @enderror
            </form>
        </div>

        {{-- Nicht zugewiesene Siegel --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-6 shadow">
            <h3 class="font-uncial text-waldritter text-base mb-3">
                Pool – nicht zugewiesene Siegel
                <span class="ui mini circular label ml-1">{{ $unassigned->count() }}</span>
            </h3>
            @if ($unassigned->isEmpty())
                <p class="text-stone-500 text-sm">Alle Siegel wurden zugewiesen oder es wurden noch keine generiert.</p>
            @else
                <div class="overflow-x-auto">
                <table class="ui very basic compact table">
                    <thead><tr>
                        <th>Siegel</th><th>Erstellt am</th><th>Aktionen</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($unassigned as $entry)
                            <tr>
                                <td><code class="font-mono tracking-widest text-waldritter">{{ $entry->code }}</code></td>
                                <td>{{ $entry->created_at->format('d.m.Y H:i') }}</td>
                                <td class="flex flex-wrap gap-1">
                                    <a href="{{ route('public.hero', $entry->code) }}" target="_blank"
                                       class="ui mini basic button" rel="noopener">
                                        Link testen
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.id-cards.destroy', $entry->code) }}"
                                          data-confirm="Siegel {{ $entry->code }} wirklich löschen?"
                                          style="display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ui mini basic red button">
                                            <i class="trash icon"></i> Löschen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>

        {{-- Zugewiesene Siegel (letzte 50) --}}
        @if ($assigned->isNotEmpty())
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-6 shadow">
            <h3 class="font-uncial text-waldritter text-base mb-3">
                Zugewiesene Siegel <span class="text-sm font-normal text-stone-500">(letzte 50)</span>
            </h3>
            <div class="overflow-x-auto">
            <table class="ui very basic compact table">
                <thead><tr>
                    <th>Siegel</th><th>Held</th><th>Zugewiesen am</th><th>Aktionen</th>
                </tr></thead>
                <tbody>
                    @foreach ($assigned as $entry)
                        <tr>
                            <td><code class="font-mono tracking-widest text-waldritter">{{ $entry->code }}</code></td>
                            <td>
                                @if ($entry->hero)
                                    <a href="{{ route('heroes.show', $entry->hero) }}"
                                       class="text-waldritter hover:underline">
                                        {{ $entry->hero->character_name }}
                                    </a>
                                @else
                                    <span class="text-stone-400">—</span>
                                @endif
                            </td>
                            <td>{{ optional($entry->assigned_at)->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($entry->hero)
                                    <a href="{{ route('admin.id-cards.reprint', $entry->hero) }}"
                                       class="ui mini basic button" target="_blank" rel="noopener">
                                        <i class="print icon"></i> Neu drucken
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
