<x-public-layout title="Helden- und Charakterverzeichnis – Heldenregister">

    <div class="max-w-lg mx-auto px-4 sm:px-6">

        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6 mb-6">

            <h1 class="font-uncial text-2xl text-waldritter mb-2 text-center">Helden- und Charakterverzeichnis</h1>
            <p class="text-stone-600 text-sm text-center mb-3">
                Gib das 6-stellige Helden-Siegel oder einen Heldennamen ein.
                Auch bekannte Gestalten aus unseren Abenteuern – etwa Bran oder die Babayaga – kannst du hier erforschen.
            </p>
            {{-- PUB-09: Hinweis auf Code-Quellen --}}
            <div class="bg-amber-50 border border-amber-200 rounded px-4 py-3 mb-5 text-xs text-amber-900 leading-snug">
                Den Code findest du auf dem <strong>Heldenausweis</strong> (ausgehändigt vom Bürokraten)
                oder als <strong>QR-Code</strong> im Helden-Detail nach der Anmeldung.
            </div>

            @if (!empty($error))
                <div class="bg-red-50 border border-red-300 text-red-800 rounded px-4 py-3 mb-5 text-sm">
                    {{ $error }}
                </div>
            @endif

            <form method="GET" action="{{ route('public.hero.search.go') }}" class="space-y-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-stone-700 mb-1">
                        Helden-Siegel oder Name
                    </label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        value="{{ $input ?? '' }}"
                        placeholder="z. B. AB3X7K oder Aldric"
                        maxlength="100"
                        autocomplete="off"
                        autofocus
                        class="block w-full rounded border-2 border-stone-300 focus:border-[#5a3a22] px-4 py-2"
                    >
                    <p class="text-xs text-stone-400 mt-1">
                        6-stelliges Siegel (direkte Weiterleitung) oder mind. 2 Zeichen Heldennamen.
                    </p>
                </div>

                <button type="submit" class="w-full ui primary button">
                    Suchen
                </button>
            </form>

        </div>

        {{-- PUB-08 / Issue #30: Suchergebnisse nach Name (Plot-Charaktere und Helden) --}}
        @isset($results)
            @php($plotResults ??= collect())
            @if ($results->isEmpty() && $plotResults->isEmpty())
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6 text-center text-stone-500">
                    Niemand mit diesem Namen gefunden.
                </div>
            @endif

            {{-- Issue #30: Bekannte Plot-Charaktere als Vorschlag-Cards --}}
            @if ($plotResults->isNotEmpty())
                <section aria-labelledby="plot-results-heading" class="mb-6">
                    <h2 id="plot-results-heading" class="font-uncial text-lg text-waldritter mb-3">Bekannte Gestalten</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($plotResults as $character)
                            @include('public._plot_character_card')
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($results->isNotEmpty())
                @if ($plotResults->isNotEmpty())
                    <h2 class="font-uncial text-lg text-waldritter mb-3">Helden</h2>
                @endif
                <div class="space-y-3">
                    @foreach ($results as $hero)
                        <a href="{{ route('public.hero', $hero->public_code) }}"
                           class="block bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4 hover:border-[#5a3a22] transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-uncial text-waldritter text-lg">{{ $hero->character_name }}</span>
                                    @if ($hero->classes->isNotEmpty())
                                        <span class="text-stone-500 text-sm ml-2">
                                            {{ $hero->classes->pluck('name')->implode(', ') }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-stone-400 text-xs font-mono tracking-widest">
                                    {{ $hero->public_code }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        @endisset

        {{-- Issue #36: Zufällige Plot-Charaktere vorschlagen, um auf sie aufmerksam zu machen --}}
        @if (($suggestions ?? collect())->isNotEmpty())
            <section aria-labelledby="plot-suggestions-heading" class="mb-6">
                <h2 id="plot-suggestions-heading" class="font-uncial text-lg text-waldritter mb-1">Bekannten Gestalten begegnen</h2>
                <p class="text-stone-600 text-sm mb-3">Wer streift durch Silvanien? Lerne die bekannten Gestalten unserer Abenteuer kennen.</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($suggestions as $character)
                        @include('public._plot_character_card')
                    @endforeach
                </div>
                <p class="text-center mt-3">
                    <a href="{{ route('public.hero.search') }}" class="text-sm text-waldritter hover:underline">
                        <i class="random icon" aria-hidden="true"></i> Andere Gestalten zeigen
                    </a>
                </p>
            </section>
        @endif

    </div>

</x-public-layout>
