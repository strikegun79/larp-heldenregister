{{-- Issue #30/#34: Öffentliche Seite eines bekannten Plot-Charakters (Eintrag der Chronik) --}}
@php
    // Der Reiter „SL-Planung“ wird nur für berechtigte, angemeldete Nutzer ausgeliefert.
    $canSeeSecret = auth()->user()?->can('plot-characters.secret') ?? false;
    $hasLore = filled($character->public_lore);
    $lists = array_filter([
        'Bekannte Titel' => $character->known_titles,
        'Besondere Fähigkeiten' => $character->abilities,
        'Bekannte Artefakte' => $character->artifacts,
        'Bekannte Verbindungen' => $character->connections,
    ]);
@endphp

<x-public-layout :title="$character->name . ' – Heldenregister'">

    <div class="max-w-2xl lg:max-w-[65rem] mx-auto px-4 sm:px-6">

        <article class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden mb-6">
            @if ($character->imageUrl())
                <img src="{{ $character->imageUrl() }}" alt="{{ $character->name }}"
                     class="w-full max-h-[28rem] object-cover border-b-2 border-[#5a3a22]/40">
            @endif

            <div class="p-6">
                {{-- Kopf: Name, Beiname, Status und Gesinnung --}}
                <div class="flex flex-wrap gap-1.5 mb-2">
                    <span class="ui tiny brown label">Bekannte Gestalt</span>
                    <span class="ui tiny {{ $character->statusColor() }} label">{{ $character->statusLabel() }}</span>
                    @if ($character->alignment !== 'unknown')
                        <span class="ui tiny {{ $character->alignmentColor() }} label">{{ $character->alignmentLabel() }}</span>
                    @endif
                </div>
                <h1 class="font-uncial text-3xl text-waldritter leading-tight">{{ $character->name }}</h1>
                @if ($character->title)
                    <p class="text-stone-600 text-lg mt-1">{{ $character->title }}</p>
                @endif

                {{-- Faktentabelle (nur bekannte Angaben) --}}
                @if ($facts = $character->facts())
                    <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm border-t border-[#5a3a22]/20 pt-4">
                        @foreach ($facts as $label => $value)
                            <div>
                                <dt class="text-xs text-stone-500 font-medium">{{ $label }}</dt>
                                <dd class="text-stone-800">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                {{-- Steckbrief --}}
                <section aria-labelledby="steckbrief-heading" class="mt-6">
                    <h2 id="steckbrief-heading" class="font-uncial text-xl text-waldritter mb-2">Steckbrief</h2>
                    @if (filled($character->description))
                        <div class="text-stone-800 leading-relaxed whitespace-pre-line">{{ $character->description }}</div>
                    @else
                        <p class="text-stone-500 italic">Über diese Gestalt ist noch wenig bekannt …</p>
                    @endif

                    @if ($character->quote)
                        <blockquote class="mt-4 border-l-4 border-[#5a3a22]/40 pl-4 text-stone-700">
                            <span class="block text-xs text-stone-500 font-medium mb-1">Bekanntester Ausspruch</span>
                            „{{ trim($character->quote, '„“"') }}“
                        </blockquote>
                    @endif

                    @if ($character->motto)
                        <p class="mt-4 italic text-stone-600">{{ $character->motto }}</p>
                    @endif
                </section>

                {{-- Listen --}}
                @if ($lists)
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach ($lists as $heading => $items)
                            <section>
                                <h2 class="font-uncial text-lg text-waldritter mb-1">{{ $heading }}</h2>
                                <ul class="list-disc pl-5 text-sm text-stone-800 space-y-0.5">
                                    @foreach ($items as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                    </div>
                @endif

                {{-- Chronik als Zeitleiste --}}
                @if ($character->chronicle)
                    <section aria-labelledby="chronik-heading" class="mt-6">
                        <h2 id="chronik-heading" class="font-uncial text-lg text-waldritter mb-2">Erwähnungen in den Chroniken</h2>
                        <ol class="border-l-2 border-[#5a3a22]/30 pl-4 space-y-2 text-sm">
                            @foreach ($character->chronicle as $entry)
                                <li class="relative">
                                    <span class="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-[#5a3a22]" aria-hidden="true"></span>
                                    @if ($entry['date'])
                                        <span class="text-stone-500 font-mono text-xs mr-1">{{ $entry['date'] }}</span>
                                    @endif
                                    @if ($entry['url'])
                                        <a href="{{ $entry['url'] }}" target="_blank" rel="noopener" class="underline text-stone-800">{{ $entry['title'] }}</a>
                                    @else
                                        <span class="text-stone-800">{{ $entry['title'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>
        </article>

        {{-- Plothintergrund: öffentlicher Reiter + geheimer Reiter für die Spielleitung --}}
        @if ($hasLore || $canSeeSecret)
            <section aria-labelledby="plot-heading" x-data="{ tab: 'lore' }"
                     class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden mb-6">
                <h2 id="plot-heading" class="sr-only">Plothintergrund</h2>

                @if ($canSeeSecret)
                    <div role="tablist" aria-label="Plothintergrund" class="flex border-b-2 border-[#5a3a22]/20 bg-[#5a3a22]/5">
                        <button type="button" role="tab" id="tab-lore" aria-controls="panel-lore"
                                :aria-selected="tab === 'lore'" @click="tab = 'lore'"
                                :class="tab === 'lore' ? 'bg-white/80 text-waldritter border-b-2 border-[#5a3a22] -mb-0.5' : 'text-stone-600'"
                                class="px-4 py-3 text-sm font-semibold uppercase tracking-wide">
                            📖 Was die Waldritter wissen
                        </button>
                        <button type="button" role="tab" id="tab-secret" aria-controls="panel-secret"
                                :aria-selected="tab === 'secret'" @click="tab = 'secret'"
                                :class="tab === 'secret' ? 'bg-white/80 text-waldritter border-b-2 border-[#5a3a22] -mb-0.5' : 'text-stone-600'"
                                class="px-4 py-3 text-sm font-semibold uppercase tracking-wide">
                            🔒 SL-Planung
                        </button>
                    </div>
                @else
                    <div class="px-5 pt-4 text-sm font-semibold uppercase tracking-wide text-waldritter">📖 Was die Waldritter wissen</div>
                @endif

                <div id="panel-lore" role="tabpanel" aria-labelledby="tab-lore" x-show="tab === 'lore'" class="p-5">
                    @if ($hasLore)
                        <div class="text-stone-800 leading-relaxed whitespace-pre-line">{{ $character->public_lore }}</div>
                    @else
                        <p class="text-stone-500 italic m-0">Die Waldritter wissen bislang nichts Genaueres.</p>
                    @endif
                </div>

                @if ($canSeeSecret)
                    <div id="panel-secret" role="tabpanel" aria-labelledby="tab-secret" x-show="tab === 'secret'" x-cloak class="p-5">
                        <p class="text-xs text-stone-500 mb-2">Nur sichtbar für Plot-Schreiber, Bürokrat, Projektleitung und Spielleitung.</p>
                        @if (filled($character->gm_notes))
                            <div class="text-stone-800 leading-relaxed whitespace-pre-line">{{ $character->gm_notes }}</div>
                        @else
                            <p class="text-stone-500 italic m-0">Noch keine Planung eingetragen.</p>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        <a href="{{ route('public.hero.search') }}" class="ui basic button">
            <i class="search icon"></i> Weiter im Heldenverzeichnis suchen
        </a>
    </div>

</x-public-layout>
