<x-app-layout>
    @php
        $category = $adventure->category && $adventure->category->id ? $adventure->category->name : null;

        $canBook   = auth()->user()->can('adventure.book');
        $regOpen   = $adventure->registrationOpen();
        $isTeamer  = auth()->user()->hasAnyRole('teamer', 'lehrmeister') && $myTeamerSignup === null;
        $canManage = auth()->user()->can('events.edit');
        // Priorität: Anmelden > Teamer-Anmeldung > Verwalten
        $primAnmelden  = $canBook && $regOpen;
        $primTeamer    = ! $primAnmelden && $isTeamer;
        $primVerwalten = ! $primAnmelden && ! $isTeamer && $canManage;
        // Overflow-Menü, wenn neben der Primäraktion noch Weiteres vorhanden ist
        $hasDropdown = $primAnmelden || ($primTeamer && $canManage) || auth()->user()->can('book-any-player');
        // Anmelden gesperrt, solange Profil oder Spieler fehlen (Grund steht sichtbar im Hinweis)
        $bookBlocked = ! $profileComplete || ! $userHasPlayers;
    @endphp

    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Seitenkopf --}}
            <div class="mb-5">
                <a href="{{ route('adventures.index') }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zu den Abenteuern
                </a>
                @if ($category)
                    <span class="wr-eyebrow">{{ $category }}</span>
                @endif
                <h1 class="wr-heading break-words hyphens-auto">{{ $adventure->name }}</h1>
                <x-wr.adventure-meta :adventure="$adventure" class="text-wr-ink-muted mb-3" />
                <div class="flex flex-wrap gap-1.5">
                    <x-wr.adventure-status :adventure="$adventure" :show-slots="false" />
                    @if ($adventure->min_age || $adventure->max_age)
                        <span class="wr-pill"><i class="child icon" aria-hidden="true"></i>{{ $adventure->age_range_label }}</span>
                    @endif
                </div>
            </div>

            <div class="wr-card p-4 sm:p-6 adv-detail-page">
                @include('adventures._detail')
            </div>

            {{-- UI-38: Aktions-Footer (auf Mobile sticky, auf Desktop inline). --}}
            <x-mobile.sticky-footer class="mt-4">
                {{-- Mobile (< sm): Primär-Button + Menü „Weitere Aktionen“ --}}
                <div class="sm:hidden flex w-full gap-2">
                    @if ($primAnmelden)
                        @if ($bookBlocked)
                            <button type="button" class="wr-btn wr-btn--primary flex-1" disabled>Anmelden</button>
                        @else
                            <a href="{{ route('adventures.bookings.create', $adventure) }}"
                               data-modal-stack="{{ route('adventures.bookings.create', $adventure) }}"
                               class="wr-btn wr-btn--primary flex-1">Anmelden</a>
                        @endif
                    @elseif ($primTeamer)
                        <a href="{{ route('adventures.teamer.create', $adventure) }}"
                           data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}"
                           class="wr-btn wr-btn--primary flex-1">Teamer-Anmeldung</a>
                    @elseif ($primVerwalten)
                        <a href="{{ route('adventures.manage', $adventure) }}"
                           class="wr-btn wr-btn--secondary flex-1">Verwalten</a>
                    @endif

                    @if ($hasDropdown)
                        <div class="ui floating dropdown wr-btn wr-btn--secondary" role="button" tabindex="0"
                             aria-label="Weitere Aktionen" aria-haspopup="menu">
                            <i class="ellipsis vertical icon m-0" aria-hidden="true"></i>
                            <div class="menu">
                                @if ($primAnmelden)
                                    @if ($userHasBookableGroups)
                                        <a class="item"
                                           href="{{ route('adventures.group-bookings.create', $adventure) }}"
                                           data-modal-stack="{{ route('adventures.group-bookings.create', $adventure) }}">
                                            <i class="users icon"></i> Gruppe anmelden
                                        </a>
                                    @endif
                                    <a class="item"
                                       href="{{ route('adventures.bookings.create-guest', $adventure) }}"
                                       data-modal-stack="{{ route('adventures.bookings.create-guest', $adventure) }}">
                                        <i class="user icon"></i> Gast anmelden (einmalig)
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
                                @can('book-any-player')
                                    <div class="divider"></div>
                                    <a class="item"
                                       href="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}"
                                       data-modal-stack="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}">
                                        <i class="shield alternate icon"></i> Admin-Anmeldung
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Desktop (sm+): alle Aktionen nebeneinander --}}
                <div class="hidden sm:flex items-center gap-2 flex-wrap">
                    @if ($primAnmelden)
                        @if ($bookBlocked)
                            <button type="button" class="wr-btn wr-btn--primary" disabled>Anmelden</button>
                        @else
                            <a href="{{ route('adventures.bookings.create', $adventure) }}"
                               data-modal-stack="{{ route('adventures.bookings.create', $adventure) }}"
                               class="wr-btn wr-btn--primary">Anmelden</a>
                            @if ($userHasBookableGroups)
                                <a href="{{ route('adventures.group-bookings.create', $adventure) }}"
                                   data-modal-stack="{{ route('adventures.group-bookings.create', $adventure) }}"
                                   class="wr-btn wr-btn--secondary">Gruppe anmelden</a>
                            @endif
                            <a href="{{ route('adventures.bookings.create-guest', $adventure) }}"
                               data-modal-stack="{{ route('adventures.bookings.create-guest', $adventure) }}"
                               class="wr-btn wr-btn--secondary">Gast anmelden <span class="font-normal">(einmalig)</span></a>
                        @endif
                    @endif
                    @if ($isTeamer)
                        <a href="{{ route('adventures.teamer.create', $adventure) }}"
                           data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}"
                           class="wr-btn wr-btn--secondary"><i class="shield alternate icon m-0" aria-hidden="true"></i> Teamer-Anmeldung</a>
                    @endif
                    @can('book-any-player')
                        <a href="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}"
                           data-modal-stack="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}"
                           class="wr-btn wr-btn--secondary">
                            <i class="shield alternate icon m-0" aria-hidden="true"></i> Admin-Anmeldung
                        </a>
                    @endcan
                    @if ($canManage)
                        <a href="{{ route('adventures.manage', $adventure) }}" class="wr-btn wr-btn--secondary">Verwalten</a>
                    @endif
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
