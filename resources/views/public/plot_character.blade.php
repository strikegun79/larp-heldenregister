{{-- Issue #30: Öffentliche Seite eines bekannten Plot-Charakters --}}
<x-public-layout :title="$character->name . ' – Heldenregister'">

    <div class="max-w-2xl mx-auto px-4 sm:px-6">

        <article class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden mb-6">
            @if ($character->imageUrl())
                <img src="{{ $character->imageUrl() }}" alt="{{ $character->name }}"
                     class="w-full max-h-[28rem] object-cover border-b-2 border-[#5a3a22]/40">
            @endif

            <div class="p-6">
                <span class="ui tiny brown label mb-2">Bekannte Gestalt</span>
                <h1 class="font-uncial text-3xl text-waldritter leading-tight">{{ $character->name }}</h1>
                @if ($character->title)
                    <p class="text-stone-600 text-lg mt-1">{{ $character->title }}</p>
                @endif

                @if (filled($character->description))
                    <div class="mt-5 text-stone-800 leading-relaxed whitespace-pre-line">{{ $character->description }}</div>
                @else
                    <p class="mt-5 text-stone-500 italic">Über diese Gestalt ist noch wenig bekannt …</p>
                @endif
            </div>
        </article>

        <a href="{{ route('public.hero.search') }}" class="ui basic button">
            <i class="search icon"></i> Weiter im Heldenverzeichnis suchen
        </a>
    </div>

</x-public-layout>
