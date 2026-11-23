@php
    Auth::user()->loadMissing(['roles', 'unreadNotifications']);
    $unreadNotes = Auth::user()->unreadNotifications;
@endphp
{{-- Hauptnavigation (Waldritter v1): Waldgrün, aktive Punkte mit goldener Unterstreichung + aria-current --}}
<nav class="wr-nav relative z-50" aria-label="Hauptnavigation">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center py-2">
                    <a href="{{ route('dashboard') }}" class="wr-nav__logo">
                        <x-application-logo class="block h-10 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden gap-6 sm:ms-8 sm:flex sm:items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Übersicht') }}
                    </x-nav-link>
                    <x-nav-link :href="route('players.index')" :active="request()->routeIs('players.*')">
                        {{ __('Spieler') }}
                    </x-nav-link>
                    @can('heldenregister.view')
                        <x-nav-link :href="route('heroes.index')" :active="request()->routeIs('heroes.*')">
                            {{ __('Helden') }}
                        </x-nav-link>
                        <x-nav-link :href="route('skills.catalog')" :active="request()->routeIs('skills.catalog')">
                            {{ __('Fertigkeiten') }}
                        </x-nav-link>
                    @endcan
                    @can('adventure.access')
                        <x-nav-link :href="route('adventures.index')" :active="request()->routeIs('adventures.*')">
                            {{ __('Abenteuer') }}
                        </x-nav-link>
                    @endcan
                    @canany(['portal.manage', 'newsletter.manage', 'survey.view', 'survey.admin', 'roles.view'])
                        <x-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">
                            {{ __('Verwaltung') }}
                        </x-nav-link>
                    @endcanany
                </div>
            </div>

            <!-- Settings Dropdown (Desktop) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="me-3">
                    <x-dropdown align="right" width="64">
                        <x-slot name="trigger">
                            <button type="button" class="wr-nav__icon-btn" aria-haspopup="true"
                                    aria-label="Benachrichtigungen{{ $unreadNotes->count() ? ', ' . $unreadNotes->count() . ' ungelesen' : ', keine neuen' }}">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.85 23.85 0 005.454-1.31A8.97 8.97 0 0118 9.75V9A6 6 0 006 9v.75a8.97 8.97 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.26 24.26 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                @if ($unreadNotes->count())
                                    <span class="wr-nav__badge" aria-hidden="true">{{ $unreadNotes->count() }}</span>
                                @endif
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Benachrichtigungen</div>
                            @forelse ($unreadNotes->take(10) as $note)
                                <x-dropdown-link :href="route('notifications.read', $note->id)">
                                    {{ $note->data['message'] ?? 'Benachrichtigung' }}
                                </x-dropdown-link>
                            @empty
                                <div class="px-4 py-2 text-sm text-gray-500">Keine neuen Benachrichtigungen.</div>
                            @endforelse
                            @if ($unreadNotes->count())
                                <div class="border-t border-gray-100"></div>
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full min-h-[44px] text-start px-4 py-2 text-sm text-stone-800 hover:bg-[#f4e8cc] focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-[#245f93] focus-visible:-outline-offset-[3px]">Alle als gelesen markieren</button>
                                </form>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="wr-nav__user" aria-haspopup="true">
                            <span class="sr-only">Benutzermenü für</span>
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-100">
                            {{ __('Rollen') }}: {{ Auth::user()->roles->pluck('label')->implode(', ') ?: __('keine') }}
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('info')">
                            {{ __('Hilfe & Übersicht') }}
                        </x-dropdown-link>
                        {{-- UI-45: Datensparmodus-Toggle --}}
                        <form method="POST" action="{{ route('save-data.toggle') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full min-h-[44px] text-start px-4 py-2 text-sm text-stone-800 hover:bg-[#f4e8cc] focus:outline-none focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-[#245f93] focus-visible:-outline-offset-[3px] transition">
                                @if($saveData ?? false)
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                                        Datenspar-Modus aktiv
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-stone-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                                        Datenspar-Modus
                                    </span>
                                @endif
                            </button>
                        </form>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Abmelden
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>

{{-- UI-42: Bottom-Navigation + „Mehr“-Dialog (nur < sm), Waldritter v1.
     „Mehr“ ist ein echter Dialog: Fokus hinein, Tab bleibt im Dialog, Escape/Schließen,
     danach Fokus zurück auf „Mehr“ (UI-46). --}}
@php
    $bottomActive = [
        'dashboard' => request()->routeIs('dashboard'),
        'heroes' => request()->routeIs('heroes.*'),
        'adventures' => request()->routeIs('adventures.*'),
        'players' => request()->routeIs('players.*'),
        'more' => request()->routeIs('profile.*', 'admin.*', 'skills.*', 'ep.*', 'notifications.*'),
    ];
@endphp
<div x-data="{
         moreOpen: false,
         oeffnen() {
             this.moreOpen = true;
             this.$nextTick(() => this.$refs.moreClose.focus());
         },
         schliessen() {
             this.moreOpen = false;
             this.$nextTick(() => this.$refs.moreBtn.focus());
         },
         fokusFalle(e) {
             const els = [...this.$refs.morePanel.querySelectorAll('a[href], button:not([disabled])')];
             if (!els.length) return;
             const first = els[0], last = els[els.length - 1];
             if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
             else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
         }
     }"
     @keydown.escape.window="if (moreOpen) schliessen()"
     class="sm:hidden">

    {{-- Fixierte 5-Punkte-Leiste --}}
    <nav class="wr-bottom-nav fixed bottom-0 inset-x-0 z-50" aria-label="Schnellnavigation">
        <div class="flex items-stretch justify-around h-16 px-1">

            {{-- Übersicht --}}
            <a href="{{ route('dashboard') }}" class="bottom-nav-item" @if ($bottomActive['dashboard']) aria-current="page" @endif>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span>Übersicht</span>
            </a>

            {{-- Helden --}}
            @can('heldenregister.view')
            <a href="{{ route('heroes.index') }}" class="bottom-nav-item" @if ($bottomActive['heroes']) aria-current="page" @endif>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <span>Helden</span>
            </a>
            @endcan

            {{-- Abenteuer --}}
            @can('adventure.access')
            <a href="{{ route('adventures.index') }}" class="bottom-nav-item" @if ($bottomActive['adventures']) aria-current="page" @endif>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                </svg>
                <span>Abenteuer</span>
            </a>
            @endcan

            {{-- Spieler --}}
            <a href="{{ route('players.index') }}" class="bottom-nav-item" @if ($bottomActive['players']) aria-current="page" @endif>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span>Spieler</span>
            </a>

            {{-- Mehr --}}
            <button type="button" x-ref="moreBtn" @click="oeffnen()"
                    aria-haspopup="dialog" :aria-expanded="moreOpen.toString()" aria-controls="more-sheet"
                    aria-label="Mehr anzeigen{{ $unreadNotes->count() ? ', ' . $unreadNotes->count() . ' ungelesene Benachrichtigungen' : '' }}"
                    @class(['bottom-nav-item', 'is-active' => $bottomActive['more']])>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <span class="relative">
                    Mehr
                    @if ($unreadNotes->count())
                        <span class="wr-bottom-nav__badge" aria-hidden="true">{{ $unreadNotes->count() }}</span>
                    @endif
                </span>
            </button>
        </div>
    </nav>

    {{-- „Mehr“-Dialog: Hintergrund + Panel --}}
    <div x-show="moreOpen" style="display:none" class="fixed inset-0 z-[55]">

        {{-- Hintergrund: Klick schließt --}}
        <div @click="schliessen()" class="absolute inset-0 bg-black/50" aria-hidden="true"></div>

        {{-- Panel --}}
        <div x-show="moreOpen" x-ref="morePanel" id="more-sheet"
             role="dialog" aria-modal="true" aria-labelledby="more-sheet-title"
             @keydown.tab="fokusFalle($event)"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-y-full opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-full opacity-0"
             class="wr-sheet absolute bottom-16 left-0 right-0 rounded-t-2xl shadow-2xl overflow-y-auto max-h-[80dvh]"
             style="display:none">

            {{-- Kopf mit Titel und Schließen --}}
            <div class="flex items-center justify-between px-5 pt-3 pb-2">
                <h2 id="more-sheet-title" class="font-heading text-lg font-bold m-0">Mehr</h2>
                <button type="button" x-ref="moreClose" @click="schliessen()"
                        class="wr-btn wr-btn--secondary wr-btn--small !px-3" aria-label="Menü schließen">
                    <i class="times icon m-0" aria-hidden="true"></i>
                </button>
            </div>

            {{-- Nutzer-Info --}}
            <div class="px-5 pb-3 border-b border-wr-border">
                <div class="font-semibold">{{ Auth::user()->name }}</div>
                <div class="text-sm text-wr-ink-muted break-all">{{ Auth::user()->email }}</div>
                <div class="text-xs text-wr-ink-muted mt-0.5">{{ Auth::user()->roles->pluck('label')->implode(', ') ?: 'keine Rolle' }}</div>
            </div>

            <div class="py-2">
                {{-- Benachrichtigungen --}}
                @if ($unreadNotes->count())
                    <h3 class="px-5 pt-2 pb-1 text-xs font-bold text-wr-ink-muted uppercase tracking-wide m-0">Benachrichtigungen</h3>
                    @foreach ($unreadNotes->take(5) as $note)
                        <a href="{{ route('notifications.read', $note->id) }}" class="wr-sheet__item">
                            <i class="bell icon" aria-hidden="true"></i>
                            <span>{{ $note->data['message'] ?? 'Benachrichtigung' }}</span>
                        </a>
                    @endforeach
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="wr-sheet__item font-semibold text-wr-forest">
                            <i class="check double icon" aria-hidden="true"></i> Alle als gelesen markieren
                        </button>
                    </form>
                    <div class="border-t border-wr-border mx-4 my-1"></div>
                @endif

                {{-- Rollenbasierte Links --}}
                @can('heldenregister.view')
                    <a href="{{ route('skills.catalog') }}" class="wr-sheet__item">
                        <i class="book icon" aria-hidden="true"></i> Fertigkeiten
                    </a>
                @endcan
                @canany(['portal.manage', 'newsletter.manage', 'survey.view', 'survey.admin', 'roles.view'])
                    <a href="{{ route('admin.index') }}" class="wr-sheet__item">
                        <i class="cogs icon" aria-hidden="true"></i> Verwaltung
                    </a>
                @endcanany

                <div class="border-t border-wr-border mx-4 my-1"></div>

                <a href="{{ route('profile.edit') }}" class="wr-sheet__item">
                    <i class="user circle icon" aria-hidden="true"></i> Profil
                </a>

                <a href="{{ route('info') }}" class="wr-sheet__item">
                    <i class="info circle icon" aria-hidden="true"></i> Hilfe &amp; Übersicht
                </a>

                {{-- UI-45: Datensparmodus-Toggle --}}
                <form method="POST" action="{{ route('save-data.toggle') }}">
                    @csrf
                    <button type="submit" class="wr-sheet__item {{ ($saveData ?? false) ? 'font-semibold text-wr-forest' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                        </svg>
                        {{ ($saveData ?? false) ? 'Datenspar-Modus aktiv' : 'Datenspar-Modus' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="wr-sheet__item wr-sheet__item--danger">
                        <i class="sign out icon" aria-hidden="true"></i> Abmelden
                    </button>
                </form>
            </div>

            {{-- Safe-area-Abstand --}}
            <div style="padding-bottom: env(safe-area-inset-bottom, 0.5rem)"></div>
        </div>
    </div>
</div>
