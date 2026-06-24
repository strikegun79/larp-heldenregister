<x-app-layout>
    @php
        $stunde = (int) now()->format('H');
        $gruss = $stunde >= 6 && $stunde < 10 ? 'Guten Morgen' : ($stunde >= 10 && $stunde < 18 ? 'Guten Tag' : 'Guten Abend');
    @endphp
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            {{ $gruss }}, <em>{{ Auth::user()->name }}</em>
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8 text-stone-800">
                <p>Willkommen im Heldenregister. Hier findest du alles rund um deine Spieler, Helden und Abenteuer.</p>
            </div>

            {{-- Admin-Kennzahlen (REP-06) --}}
            @if (! empty($metrics))
                <div class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-6 sm:mb-8">
                    @foreach ([
                        ['Spieler', $metrics['players']],
                        ['Helden', $metrics['heroes']],
                        ['Kommende Abenteuer', $metrics['upcoming_events']],
                        ['Offene Anmeldungen', $metrics['open_bookings']],
                    ] as [$label, $value])
                        <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 text-center">
                            <div class="text-3xl font-semibold text-waldritter">{{ $value }}</div>
                            <div class="text-sm text-stone-600">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- UI-43: Mobile-Dashboard (< sm) --}}
            <div class="sm:hidden space-y-4 mb-4">

                {{-- Aktiver Held --}}
                @if ($activeHero)
                    <a href="{{ route('heroes.show', $activeHero) }}"
                       class="flex items-center gap-4 bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 shadow-sm active:bg-amber-50 transition-colors">
                        <img src="{{ $activeHero->image_url }}" alt="{{ $activeHero->character_name }}"
                             class="w-16 h-16 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-stone-400 uppercase tracking-wide mb-0.5">Mein aktiver Held</div>
                            <div class="font-uncial text-waldritter text-lg leading-tight truncate">{{ $activeHero->character_name }}</div>
                            <div class="text-xs text-stone-500 truncate">{{ $activeHero->classes->pluck('name')->implode(', ') ?: '—' }}</div>
                            <div class="text-sm font-semibold text-waldritter mt-1">{{ number_format($activeHero->ep_balance, 0, ',', '.') }} EP verfügbar</div>
                        </div>
                        <svg class="h-5 w-5 text-stone-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endif

                {{-- Nächstes Abenteuer --}}
                @if ($nextAdventure)
                    <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 shadow-sm">
                        <div class="text-xs text-stone-400 uppercase tracking-wide mb-1">Nächstes Abenteuer</div>
                        <div class="font-uncial text-waldritter text-lg leading-tight mb-2">{{ $nextAdventure->name }}</div>
                        <dl class="text-sm text-stone-600 space-y-0.5 mb-3">
                            @if ($nextAdventure->start_at)
                                <div class="flex gap-2">
                                    <dt class="text-stone-400 shrink-0">Datum</dt>
                                    <dd>{{ $nextAdventure->start_at->format('d.m.Y') }}</dd>
                                </div>
                            @endif
                            @if ($nextAdventure->location)
                                <div class="flex gap-2">
                                    <dt class="text-stone-400 shrink-0">Ort</dt>
                                    <dd class="truncate">{{ $nextAdventure->location->titel }}</dd>
                                </div>
                            @endif
                            <div class="flex gap-2">
                                <dt class="text-stone-400 shrink-0">Beitrag</dt>
                                <dd>
                                    @if ($nextAdventure->fee > 0)
                                        {{ number_format($nextAdventure->fee, 2, ',', '.') }} €
                                    @else
                                        kostenlos
                                    @endif
                                </dd>
                            </div>
                        </dl>
                        @if ($alreadyBooked)
                            <span class="text-green-700 text-sm font-medium">&#10003; Du bist bereits angemeldet</span>
                        @elseif ($nextAdventure->isRegistrationOpen())
                            <a href="{{ route('adventures.show', $nextAdventure) }}"
                               class="ui primary button">Jetzt anmelden</a>
                        @else
                            <a href="{{ route('adventures.show', $nextAdventure) }}"
                               class="ui button">Details ansehen</a>
                        @endif
                    </div>
                @endif

                {{-- Leerzustand: kein Held, kein Abenteuer --}}
                @if (! $activeHero && ! $nextAdventure)
                    <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-6 text-center text-stone-500">
                        <p class="font-uncial text-waldritter text-lg mb-1">Herzlich willkommen!</p>
                        <p class="text-sm">Erkunde die Abenteuer oder lege deinen ersten Spieler an.</p>
                        <div class="flex justify-center gap-3 mt-4 flex-wrap">
                            @can('adventure.access')
                                <a href="{{ route('adventures.index') }}" class="ui button">Abenteuer</a>
                            @endcan
                            <a href="{{ route('players.index') }}" class="ui button">Meine Spieler</a>
                        </div>
                    </div>
                @endif

            </div>

            {{-- Desktop: Kachel-Navigation (sm+) --}}
            <div class="hidden sm:grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Dein Profil --}}
                <a href="{{ route('profile.edit') }}"
                   class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                    <div class="h-44 overflow-hidden">
                        <img src="/images/dein_profil.jpg" alt="" aria-hidden="true" loading="lazy" width="400" height="176" class="w-full h-full object-cover group-hover:scale-105 transition">
                    </div>
                    <div class="p-4 text-center">
                        <div class="font-uncial text-lg text-waldritter">Dein Profil</div>
                        <div class="text-sm text-stone-600">Persönlich</div>
                    </div>
                </a>

                {{-- Heldenregister --}}
                @can('heldenregister.view')
                    <a href="{{ route('heroes.index') }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="h-44 overflow-hidden">
                            <img src="/images/heroes_db.jpg" alt="" aria-hidden="true" loading="lazy" width="400" height="176" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">Heldenregister</div>
                            <div class="text-sm text-stone-600">Auflistung aller Helden</div>
                        </div>
                    </a>
                @endcan

                {{-- Abenteuer --}}
                @can('adventure.access')
                    <a href="{{ route('adventures.index') }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="h-44 overflow-hidden">
                            <img src="/images/abenteuer_v2.jpg" alt="" aria-hidden="true" loading="lazy" width="400" height="176" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">Abenteuer</div>
                            <div class="text-sm text-stone-600">Veranstaltungen</div>
                        </div>
                    </a>
                @endcan

                {{-- Deine Spieler --}}
                <a href="{{ route('players.index') }}"
                   class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                    <div class="h-44 overflow-hidden">
                        <img src="/images/heldenarchiv.jpg" alt="" aria-hidden="true" loading="lazy" width="400" height="176" class="w-full h-full object-cover group-hover:scale-105 transition">
                    </div>
                    <div class="p-4 text-center">
                        <div class="font-uncial text-lg text-waldritter">Deine Spieler</div>
                        <div class="text-sm text-stone-600">Spielerdatenbank</div>
                    </div>
                </a>

                {{-- Verwaltung (nur Admins) --}}
                @can('portal.manage')
                    <a href="{{ route('admin.index') }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="h-44 overflow-hidden">
                            <img src="/images/administration.jpg" alt="" aria-hidden="true" loading="lazy" width="400" height="176" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">Verwaltung</div>
                            <div class="text-sm text-stone-600">Portal-Administration</div>
                        </div>
                    </a>
                @endcan
            </div>

        </div>
    </div>
</x-app-layout>
