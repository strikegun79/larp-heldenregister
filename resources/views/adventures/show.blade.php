<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <a href="{{ route('adventures.index') }}"
                   class="text-sm text-stone-500 hover:text-waldritter mb-1 inline-flex items-center gap-1">
                    <i class="arrow left icon" style="font-size:.8em"></i> Zurück
                </a>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $adventure->name }}</h2>
                <p class="text-sm text-stone-500 mt-0.5">
                    {{ optional($adventure->start_at)->format('d.m.Y') }}
                    @if ($adventure->location)
                        · {{ $adventure->location->titel }}
                    @endif
                    @if ($adventure->status)
                        · <span class="inline-block rounded px-2 py-0.5 text-xs"
                                style="background: {{ $adventure->status->color }}33;">{{ $adventure->status->description }}</span>
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4 sm:p-6 adv-detail-page">
                @include('adventures._detail')
            </div>

            {{-- UI-38: Aktions-Footer (auf Mobile sticky, auf Desktop inline). --}}
            <x-mobile.sticky-footer class="mt-4">
                @php
                    $canBook    = auth()->user()->can('adventure.book');
                    $regOpen    = $adventure->registrationOpen();
                    $isTeamer   = auth()->user()->hasAnyRole('teamer', 'lehrmeister') && $myTeamerSignup === null;
                    $canManage  = auth()->user()->can('events.edit');
                    // Priorität: Anmelden > Teamer-Anmeldung > Verwalten
                    $primAnmelden = $canBook && $regOpen;
                    $primTeamer   = !$primAnmelden && $isTeamer;
                    $primVerwalten = !$primAnmelden && !$isTeamer && $canManage;
                    // Overflow-Dropdown zeigen wenn neben der Primäraktion noch Weiteres vorhanden ist
                    $hasDropdown  = $primAnmelden || ($primTeamer && $canManage);
                    // Anmelde-Button deaktivieren wenn Profil oder Spieler fehlt
                    $bookBlockedReason = null;
                    if (! $profileComplete && ! $userHasPlayers) {
                        $bookBlockedReason = 'Bitte vervollständige zuerst dein Profil und lege einen Spieler an.';
                    } elseif (! $profileComplete) {
                        $bookBlockedReason = 'Bitte vervollständige zuerst dein Profil, bevor du dich anmelden kannst.';
                    } elseif (! $userHasPlayers) {
                        $bookBlockedReason = 'Du benötigst zunächst einen Spieler, um dich anmelden zu können.';
                    }
                @endphp

                {{-- Mobile (< sm): Primär-Button + Overflow-Dropdown --}}
                <div class="sm:hidden flex w-full gap-2">
                    @if ($primAnmelden)
                        @if ($bookBlockedReason)
                            <span data-tooltip="{{ $bookBlockedReason }}" data-position="top center" data-inverted="" style="flex:1; display:flex;">
                                <span class="ui primary button disabled" style="flex:1; pointer-events:none;" aria-disabled="true">Anmelden</span>
                            </span>
                        @else
                            <a href="{{ route('adventures.bookings.create', $adventure) }}"
                               data-modal-stack="{{ route('adventures.bookings.create', $adventure) }}"
                               class="ui primary button" style="flex:1">Anmelden</a>
                        @endif
                    @elseif ($primTeamer)
                        <a href="{{ route('adventures.teamer.create', $adventure) }}"
                           data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}"
                           class="ui teal button" style="flex:1">Teamer-Anmeldung</a>
                    @elseif ($primVerwalten)
                        <a href="{{ route('adventures.manage', $adventure) }}"
                           class="ui button" style="flex:1">Verwalten</a>
                    @endif

                    @if ($hasDropdown)
                        <div class="ui floating dropdown icon button">
                            <i class="ellipsis vertical icon"></i>
                            <div class="menu">
                                @if ($primAnmelden)
                                    <a class="item"
                                       href="{{ route('adventures.group-bookings.create', $adventure) }}"
                                       data-modal-stack="{{ route('adventures.group-bookings.create', $adventure) }}">
                                        <i class="users icon"></i> Gruppe anmelden
                                    </a>
                                    <a class="item"
                                       href="{{ route('adventures.bookings.create-guest', $adventure) }}"
                                       data-modal-stack="{{ route('adventures.bookings.create-guest', $adventure) }}">
                                        <i class="user outline icon"></i> Gast anmelden
                                    </a>
                                    @if ($isTeamer || $canManage)
                                        <div class="divider"></div>
                                    @endif
                                    @if ($isTeamer)
                                        <a class="item"
                                           href="{{ route('adventures.teamer.create', $adventure) }}"
                                           data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}">
                                            <i class="shield alternate icon"></i> Teamer-Anmeldung
                                        </a>
                                    @endif
                                    @if ($canManage)
                                        <a class="item" href="{{ route('adventures.manage', $adventure) }}">
                                            <i class="cog icon"></i> Verwalten
                                        </a>
                                    @endif
                                @elseif ($primTeamer && $canManage)
                                    <a class="item" href="{{ route('adventures.manage', $adventure) }}">
                                        <i class="cog icon"></i> Verwalten
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Desktop (sm+): Alle Buttons inline --}}
                <div class="hidden sm:flex items-center gap-3 flex-wrap">
                    @can('adventure.book')
                        @if ($adventure->registrationOpen())
                            @if ($bookBlockedReason)
                                <span data-tooltip="{{ $bookBlockedReason }}" data-position="top center" data-inverted="">
                                    <span class="ui primary button disabled" style="pointer-events:none;" aria-disabled="true">Anmelden</span>
                                </span>
                            @else
                                <a href="{{ route('adventures.bookings.create', $adventure) }}"
                                   data-modal-stack="{{ route('adventures.bookings.create', $adventure) }}"
                                   class="ui primary button">Anmelden</a>
                                <a href="{{ route('adventures.group-bookings.create', $adventure) }}"
                                   data-modal-stack="{{ route('adventures.group-bookings.create', $adventure) }}"
                                   class="ui button">Gruppe anmelden</a>
                                <a href="{{ route('adventures.bookings.create-guest', $adventure) }}"
                                   data-modal-stack="{{ route('adventures.bookings.create-guest', $adventure) }}"
                                   class="ui button">Gast anmelden</a>
                            @endif
                        @endif
                    @endcan
                    @if (auth()->user()->hasAnyRole('teamer', 'lehrmeister') && $myTeamerSignup === null)
                        <a href="{{ route('adventures.teamer.create', $adventure) }}"
                           data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}"
                           class="ui teal button">Teamer-Anmeldung</a>
                    @endif
                    @can('events.edit')
                        <a href="{{ route('adventures.manage', $adventure) }}" class="ui button">Verwalten</a>
                    @endcan
                    <a href="{{ route('adventures.index') }}" class="ui button">&larr; Zurück</a>
                </div>
            </x-mobile.sticky-footer>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.menu .item[data-tab]').tab();
            $('.ui.floating.dropdown').dropdown();
        });
    </script>
    @endpush
</x-app-layout>
