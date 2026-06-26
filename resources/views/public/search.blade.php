<x-public-layout title="Helden suchen – Heldenregister">

    <div class="max-w-lg mx-auto px-4 sm:px-6">

        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6 mb-6">

            <h1 class="font-uncial text-2xl text-waldritter mb-2 text-center">Helden suchen</h1>
            <p class="text-stone-600 text-sm text-center mb-6">
                Gib den 6-stelligen Helden-Code oder einen Heldennamen ein.
            </p>

            @if (!empty($error))
                <div class="bg-red-50 border border-red-300 text-red-800 rounded px-4 py-3 mb-5 text-sm">
                    {{ $error }}
                </div>
            @endif

            <form method="GET" action="{{ route('public.hero.search.go') }}" class="space-y-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-stone-700 mb-1">
                        Helden-Code oder Name
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
                        6-stelliger Code (direkte Weiterleitung) oder mind. 2 Zeichen Heldennamen.
                    </p>
                </div>

                <button type="submit" class="w-full ui primary button">
                    Suchen
                </button>
            </form>

        </div>

        {{-- PUB-08: Suchergebnisse nach Name --}}
        @isset($results)
            @if ($results->isEmpty())
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6 text-center text-stone-500">
                    Kein Held mit diesem Namen gefunden.
                </div>
            @else
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

    </div>

</x-public-layout>
