<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Versionsverlauf
            </h2>
            @if ($version)
                <span class="text-sm text-stone-500">Aktuelle Version {{ $version }}</span>
            @endif
        </div>
    </x-slot>

    {{-- Gestaltung des aus Markdown erzeugten HTML (kein Typography-Plugin im Projekt) --}}
    <style>
        .changelog h2 { font-family: 'Uncial Antiqua', serif; font-size: 1.5rem; color: #5a3a22; margin: 2rem 0 .75rem; padding-top: 1.5rem; border-top: 2px solid rgba(90, 58, 34, .2); }
        .changelog h2:first-of-type { margin-top: 0; padding-top: 0; border-top: 0; }
        .changelog h3 { font-weight: 700; color: #44403c; margin: 1.25rem 0 .5rem; }
        .changelog p { color: #44403c; margin-bottom: .75rem; line-height: 1.6; }
        .changelog ul { list-style: disc; padding-left: 1.5rem; margin-bottom: .75rem; }
        .changelog li { color: #44403c; margin-bottom: .35rem; line-height: 1.5; }
        .changelog a { color: #5a3a22; text-decoration: underline; }
        .changelog strong { color: #292524; }
    </style>

    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="changelog bg-[#fdf6e3] border-2 border-[#5a3a22]/30 rounded-xl p-6 sm:p-8">
                @if ($html !== '')
                    {!! $html !!}
                @else
                    <p>Der Versionsverlauf ist derzeit nicht verfügbar.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
