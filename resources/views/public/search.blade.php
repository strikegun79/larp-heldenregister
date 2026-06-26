<x-public-layout title="Helden suchen – Heldenregister">

    <div class="max-w-md mx-auto px-4 sm:px-6">

        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">

            <h1 class="font-uncial text-2xl text-waldritter mb-2 text-center">Helden suchen</h1>
            <p class="text-stone-600 text-sm text-center mb-6">
                Gib den 6-stelligen Helden-Code ein, den du auf deinem Charakterbogen findest.
            </p>

            @if (!empty($error))
                <div class="bg-red-50 border border-red-300 text-red-800 rounded px-4 py-3 mb-5 text-sm">
                    {{ $error }}
                </div>
            @endif

            <form method="GET" action="{{ route('public.hero.search.go') }}" class="space-y-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-stone-700 mb-1">
                        Helden-Code
                    </label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        value="{{ $input ?? '' }}"
                        placeholder="z. B. AB3X7K"
                        maxlength="6"
                        autocomplete="off"
                        autofocus
                        class="block w-full rounded border-2 border-stone-300 focus:border-[#5a3a22] px-4 py-2 text-center font-mono tracking-widest text-lg uppercase placeholder:normal-case placeholder:tracking-normal placeholder:font-sans placeholder:text-base"
                        style="text-transform: uppercase;"
                    >
                    <p class="text-xs text-stone-400 mt-1">6 Zeichen, Buchstaben und Zahlen (keine Verwechslungszeichen).</p>
                </div>

                <button type="submit"
                        class="w-full ui primary button">
                    Helden finden
                </button>
            </form>

        </div>

    </div>

</x-public-layout>
