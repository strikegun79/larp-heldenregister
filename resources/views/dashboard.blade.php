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

            {{-- Onboarding-Fortschrittsbox: solange nicht alle drei Aufgaben erledigt sind --}}
            @if ($showOnboarding)
                <section
                    role="region"
                    aria-labelledby="onboarding-heading"
                    x-data="{ elternOffen: false }"
                    class="bg-amber-50/90 border-2 border-[#5a3a22]/40 rounded-lg p-4 sm:p-6 mb-6 sm:mb-8 text-stone-800">

                    <h2 id="onboarding-heading" class="font-uncial text-xl text-waldritter mb-1">
                        Deine ersten Schritte
                    </h2>
                    <p class="text-stone-600 text-sm mb-5">
                        Herzlich willkommen bei den Waldritter! Erledige diese drei Aufgaben, um loszulegen.
                    </p>

                    <ol class="space-y-4 mb-5">

                        {{-- Schritt 1: Profil vervollständigen --}}
                        <li class="flex gap-3 items-start">
                            @if ($profileComplete)
                                <span aria-label="Erledigt"
                                      class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-green-600 text-white text-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            @else
                                <span aria-hidden="true"
                                      class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[#5a3a22] text-amber-50 font-semibold text-sm">1</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold {{ $profileComplete ? 'line-through text-stone-400' : 'text-waldritter' }}">
                                    Profil vervollständigen
                                </p>
                                @unless ($profileComplete)
                                    <p class="text-sm text-stone-600 mt-0.5 mb-2">
                                        Trage deine Adresse und Telefonnummer ein – diese werden für die Anmeldung zu Veranstaltungen benötigt.
                                    </p>
                                    <a href="{{ route('profile.edit') }}"
                                       class="ui small primary button focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-2">
                                        Zum Profil
                                    </a>
                                @endunless
                            </div>
                        </li>

                        {{-- Schritt 2: Spieler anlegen --}}
                        <li class="flex gap-3 items-start {{ ! $profileComplete ? 'opacity-50' : '' }}">
                            @if ($hasPlayers)
                                <span aria-label="Erledigt"
                                      class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-green-600 text-white text-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            @else
                                <span aria-hidden="true"
                                      class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[#5a3a22] text-amber-50 font-semibold text-sm">2</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold {{ $hasPlayers ? 'line-through text-stone-400' : 'text-waldritter' }}">
                                    Spieler anlegen
                                </p>
                                @unless ($hasPlayers)
                                    <p class="text-sm text-stone-600 mt-0.5 mb-2">
                                        Lege deinen Spieler (oder dein Kind) an – das geht direkt hier im Portal.
                                    </p>
                                    <div class="flex flex-wrap gap-3 items-center mb-2">
                                        <a href="{{ route('players.create') }}"
                                           class="ui small {{ $profileComplete ? 'primary' : '' }} button focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-2">
                                            Spieler anlegen
                                        </a>
                                    </div>
                                    <p class="text-xs text-stone-500">
                                        Existiert ein Spieler bereits über ein anderes Elternteil?
                                        Schreibe uns bitte eine E-Mail an
                                        <a href="mailto:{{ config('portal.contact_email') }}"
                                           class="underline hover:text-stone-800">{{ config('portal.contact_email') }}</a>,
                                        damit wir den Spieler deinem Konto zuordnen können.
                                    </p>
                                @endunless
                            </div>
                        </li>

                        {{-- Schritt 3: Zur Veranstaltung anmelden --}}
                        <li class="flex gap-3 items-start {{ ! $hasPlayers ? 'opacity-50' : '' }}">
                            @if ($hasBookings)
                                <span aria-label="Erledigt"
                                      class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-green-600 text-white text-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            @else
                                <span aria-hidden="true"
                                      class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[#5a3a22] text-amber-50 font-semibold text-sm">3</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold {{ $hasBookings ? 'line-through text-stone-400' : 'text-waldritter' }}">
                                    Zu einer Veranstaltung anmelden
                                </p>
                                @unless ($hasBookings)
                                    <p class="text-sm text-stone-600 mt-0.5 mb-2">
                                        Such dir ein Abenteuer aus und melde deinen Spieler an. Deinen Helden erstellst du dann direkt vor Ort
                                        beim <strong class="font-medium">Bürokraten</strong>.
                                    </p>
                                    @can('adventure.access')
                                        <a href="{{ route('adventures.index') }}"
                                           class="ui small {{ $hasPlayers ? 'primary' : '' }} button focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-2">
                                            Abenteuer ansehen
                                        </a>
                                    @endcan
                                @endunless
                            </div>
                        </li>

                    </ol>

                    <div class="pt-4 border-t border-[#5a3a22]/20">
                        <button type="button"
                                x-on:click="elternOffen = !elternOffen"
                                :aria-expanded="elternOffen.toString()"
                                aria-controls="onboarding-eltern"
                                class="text-sm text-stone-600 hover:text-stone-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-2">
                            Infos für Eltern &amp; Erziehungsberechtigte
                        </button>
                        <div id="onboarding-eltern"
                             x-show="elternOffen"
                             x-cloak
                             class="mt-3 text-sm text-stone-700 space-y-2">
                            <p>Bitte legt zunächst euer Kind als Spieler an und meldet es zu einer Veranstaltung an. Auf der Veranstaltung erstellt die Spielleitung gemeinsam mit eurem Kind den Charakter (Helden). Der Bürokrat ist dabei ein Spielcharakter (NSC), der als Ansprechperson für das Heldenregister zuständig ist. Erst nach der ersten Veranstaltung erscheint der Held hier im Portal.</p>
                            <p>Für Kinder unter 18 Jahren ist die Anmeldung nur mit Einwilligung der Erziehungsberechtigten möglich.</p>
                        </div>
                    </div>
                </section>
            @endif

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
                             class="w-16 h-16 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0" loading="lazy">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-stone-500 uppercase tracking-wide mb-0.5">
                                {{ $activeHeroIsOwn ? 'Mein aktiver Held' : 'Held von ' . $activePlayer->name }}
                            </div>
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
                    <a href="{{ route('adventures.show', $nextAdventure) }}"
                       class="block bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 shadow-sm active:bg-amber-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
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
                        <div class="flex items-center justify-between">
                            @if ($alreadyBooked)
                                <span class="text-green-700 text-sm font-medium">&#10003; Angemeldet</span>
                            @elseif ($nextAdventure->registrationOpen())
                                <span class="ui small primary button pointer-events-none">Jetzt anmelden</span>
                            @else
                                <span class="text-stone-400 text-sm">Details ansehen</span>
                            @endif
                            <svg class="h-5 w-5 text-stone-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
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

            {{-- Desktop: Nächstes Abenteuer (sm+) --}}
            @if ($nextAdventure)
                @can('adventure.access')
                <a href="{{ route('adventures.show', $nextAdventure) }}"
                   class="hidden sm:flex items-center gap-6 bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-5 shadow mb-6 hover:shadow-xl hover:-translate-y-0.5 transition-all group focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-stone-400 uppercase tracking-wide mb-1">Nächstes Abenteuer</div>
                        <div class="font-uncial text-waldritter text-xl leading-tight mb-2 group-hover:text-amber-700 transition-colors">{{ $nextAdventure->name }}</div>
                        <dl class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-stone-600">
                            @if ($nextAdventure->start_at)
                                <div class="flex items-center gap-1.5">
                                    <dt><i class="calendar alternate outline icon text-stone-400" aria-hidden="true"></i></dt>
                                    <dd>{{ $nextAdventure->start_at->format('d.m.Y') }}</dd>
                                </div>
                            @endif
                            @if ($nextAdventure->location)
                                <div class="flex items-center gap-1.5">
                                    <dt><i class="map marker alternate icon text-stone-400" aria-hidden="true"></i></dt>
                                    <dd>{{ $nextAdventure->location->titel }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center gap-1.5">
                                <dt><i class="euro sign icon text-stone-400" aria-hidden="true"></i></dt>
                                <dd>{{ $nextAdventure->fee > 0 ? number_format($nextAdventure->fee, 2, ',', '.') . ' €' : 'kostenlos' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        @if ($alreadyBooked)
                            <span class="text-green-700 text-sm font-medium">&#10003; Angemeldet</span>
                        @elseif ($nextAdventure->registrationOpen())
                            <span class="ui small primary button pointer-events-none">Jetzt anmelden</span>
                        @else
                            <span class="ui small button pointer-events-none">Details ansehen</span>
                        @endif
                        <svg class="h-5 w-5 text-stone-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endcan
            @endif

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
                            <div class="font-uncial text-lg text-waldritter">Heldenverwaltung</div>
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
                        <img src="/images/spieler_verwaltung2.jpg" alt="" aria-hidden="true" loading="lazy" width="400" height="176" class="w-full h-full object-cover group-hover:scale-105 transition">
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
                            <img src="/images/verwaltung.jpg" alt="" aria-hidden="true" loading="lazy" width="400" height="176" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">Verwaltung</div>
                            <div class="text-sm text-stone-600">Portal-Administration</div>
                        </div>
                    </a>
                @endcan
            </div>

            {{-- Desktop: Aktiver Held (sm+) --}}
            @if ($activeHero)
                <div class="hidden sm:block mt-6 pt-6 border-t border-[#5a3a22]/30">
                    <a href="{{ route('heroes.show', $activeHero) }}"
                       class="flex items-center gap-4 bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 shadow hover:shadow-xl hover:-translate-y-0.5 transition-all group focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                        <img src="{{ $activeHero->image_url }}" alt="{{ $activeHero->character_name }}"
                             class="w-16 h-16 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0" loading="lazy">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-stone-500 uppercase tracking-wide mb-0.5">
                                {{ $activeHeroIsOwn ? 'Mein aktiver Held' : 'Held von ' . $activePlayer->name }}
                            </div>
                            <div class="font-uncial text-waldritter text-lg leading-tight truncate">{{ $activeHero->character_name }}</div>
                            <div class="text-xs text-stone-500 truncate">{{ $activeHero->classes->pluck('name')->implode(', ') ?: '—' }}</div>
                            <div class="text-sm font-semibold text-waldritter mt-1">{{ number_format($activeHero->ep_balance, 0, ',', '.') }} EP verfügbar</div>
                        </div>
                        <svg class="h-5 w-5 text-stone-400 shrink-0 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
