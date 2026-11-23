<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Plot-Charaktere</h2>
    </x-slot>

    {{-- Issue #30: Bekannte Plot-Charaktere für das öffentliche Heldenverzeichnis --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-stone-600 mb-4">
                Sichtbare Plot-Charaktere können im öffentlichen
                <a href="{{ route('public.hero.search') }}" class="underline">Heldenverzeichnis</a> gefunden werden.
            </p>

            <div class="mb-4 flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.plot-characters.create') }}" data-inspector-new="{{ route('admin.plot-characters.create') }}" class="ui primary button">Neuer Plot-Charakter</a>

                <form method="GET" action="{{ route('admin.plot-characters.index') }}" class="ui action input" role="search">
                    <label for="plot-search" class="sr-only">Plot-Charaktere durchsuchen</label>
                    <input type="search" id="plot-search" name="q" value="{{ $search }}" placeholder="Name oder Titel …">
                    <button type="submit" class="ui button"><i class="search icon m-0"></i><span class="sr-only">Suchen</span></button>
                </form>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Bild</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Titel</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($characters as $character)
                            <tr data-inspector-url="{{ route('admin.plot-characters.edit', $character) }}?inspector=1" tabindex="0"
                                aria-label="{{ $character->name }} bearbeiten">
                                <td class="px-6 py-4" data-label="Bild">
                                    @if ($character->imageUrl())
                                        <img src="{{ $character->imageUrl() }}" alt="" class="h-10 w-10 object-cover rounded border border-stone-200" loading="lazy">
                                    @else
                                        <span class="text-stone-400 text-xs">–</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4" data-label="Name">{{ $character->name }}</td>
                                <td class="px-6 py-4 text-sm text-stone-600" data-label="Titel">{{ $character->title ?: '–' }}</td>
                                <td class="px-6 py-4" data-label="Status">
                                    @if ($character->public_visible)
                                        <span class="text-green-700">öffentlich</span>
                                    @else
                                        <span class="text-stone-500">verborgen</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{-- Bearbeiten und Löschen im Inspektor (Zeilenklick / Enter) --}}
                                    <i class="chevron right icon text-wr-ink-muted m-0" aria-hidden="true"></i>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-stone-500">
                                {{ $search !== '' ? 'Keine Plot-Charaktere zu „'.$search.'“ gefunden.' : 'Noch keine Plot-Charaktere angelegt.' }}
                            </td></tr>
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
