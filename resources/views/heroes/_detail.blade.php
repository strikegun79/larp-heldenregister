{{-- Heldenbuch (Waldritter v1): eigener .wr-v1-Wrapper, weil die Ansicht auch im globalen Modal geladen wird. --}}
<div class="wr-v1">
<span data-modal-title hidden>{{ $hero->character_name ?? 'Held' }}</span>

@php($learnedIds = $hero->skills->pluck('id'))
@php($canEditPhoto = auth()->user()?->can('heldenregister.edit')
    || optional($hero->player)->users->contains('id', auth()->id()))
{{-- PUB-07: Sichtbarkeit/Suche ändern darf: heldenregister.edit ODER Betreuer des Spielers. --}}
@php($canManagePublic = auth()->user()?->can('heldenregister.edit')
    || optional($hero->player)->users->contains('id', auth()->id()))

<div id="skilltree"
     data-learn-url="{{ route('heroes.skills.store', $hero) }}"
     data-balance="{{ $hero->ep_balance }}"
     data-can-edit="{{ auth()->user()?->can('heldenregister.edit') ? 1 : 0 }}"
     data-can-bypass-lock="{{ auth()->user()?->can('heldenregister.edit') ? 1 : 0 }}">

    {{-- UI-33/UI-40: Mobile Accordion (< sm) --}}
    <div class="sm:hidden space-y-2">

        {{-- Übersicht --}}
        <x-mobile.accordion-section title="Übersicht" :open="true">
            @include('heroes.partials._overview', ['ctx' => 'm'])
        </x-mobile.accordion-section>

        {{-- UI-33: Fertigkeiten – alle Klassen in einem Accordion mit Klassen-Pills --}}
        @if ($hero->classes->isNotEmpty())
        <x-mobile.accordion-section title="Fertigkeiten">
            @include('heroes.partials._skills', ['ctx' => 'm'])
        </x-mobile.accordion-section>
        @endif

        {{-- Abenteuer --}}
        <x-mobile.accordion-section :title="'Abenteuer (' . $hero->adventure_history->count() . ')'">
            @include('heroes.partials._adventures')
        </x-mobile.accordion-section>

        {{-- EP-Verlauf --}}
        <x-mobile.accordion-section title="EP-Verlauf">
            @include('heroes.partials._ep', ['ctx' => 'm'])
        </x-mobile.accordion-section>

        {{-- Galerie (HERO-24) --}}
        @if ($hero->galleryImages->isNotEmpty() || $canEditPhoto)
        <x-mobile.accordion-section title="Galerie">
            @include('heroes.partials._gallery', ['ctx' => 'm'])
        </x-mobile.accordion-section>
        @endif

        {{-- UI-33: Verwalten ans Ende (nach spielrelevanten Sektionen) --}}
        @can('heldenregister.edit')
        <x-mobile.accordion-section title="Verwalten">
            <div class="wr-verwalten-stack">

                {{-- Helden-Siegel --}}
                <div class="wr-verwalten-panel wr-verwalten-siegel">
                    <h5 class="wr-verwalten-heading">
                        <i class="id badge icon"></i> Helden-Siegel
                    </h5>
                    <form method="POST" action="{{ route('heroes.assign-code', $hero) }}" data-refresh-modal class="ui form">
                        @csrf @method('PATCH')
                        <div class="flex items-end gap-2 flex-wrap">
                            <div class="field !mb-0">
                                <input type="text" name="code" maxlength="6" minlength="6"
                                       pattern="[A-HJ-NP-Z2-9]{6}"
                                       placeholder="XXXXXX"
                                       value="{{ old('code', $hero->public_code) }}"
                                       class="font-mono tracking-widest w-28 siegel-input"
                                       style="text-transform:uppercase"
                                       required>
                            </div>
                            <button type="submit" class="ui primary mini button">Siegel zuweisen</button>
                        </div>
                    </form>
                    @error('code') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    <p class="wr-verwalten-hint mt-1">6 Zeichen aus A–Z (kein I/L/O) und 2–9</p>
                    @if ($hero->public_code)
                        <div class="mt-3">
                            <a href="{{ route('admin.id-cards.reprint', $hero) }}" class="ui mini basic button" target="_blank" rel="noopener">
                                <i class="print icon"></i> Ausweis drucken
                            </a>
                        </div>
                    @endif
                    @if ($hero->player)
                        {{-- Willkommens-Mail: eigener Unterbereich --}}
                        <div class="wr-verwalten-mail-box mt-3">
                            <p class="wr-verwalten-mail-label">
                                <i class="envelope icon"></i> Aufnahme-Bestätigung
                            </p>
                            <form method="POST" action="{{ route('heroes.welcome-mail', $hero) }}" data-refresh-modal>
                                @csrf
                                <button type="submit" class="ui mini teal button">
                                    <i class="paper plane icon"></i> Mail senden
                                </button>
                            </form>
                            <p class="wr-verwalten-hint mt-1">Sendet die Bestätigung an Eltern/Betreuer.</p>
                        </div>
                    @endif
                </div>

                {{-- Verschollen / Wiedergefunden --}}
                <div class="wr-verwalten-panel {{ $hero->died ? 'wr-verwalten-verschollen-aktiv' : 'wr-verwalten-verschollen-warnung' }}">
                    <h5 class="wr-verwalten-heading">
                        <i class="user {{ $hero->died ? 'check circle' : 'times circle' }} icon"></i>
                        {{ $hero->died ? 'Held verschollen' : 'Held aktiv' }}
                    </h5>
                    @if ($hero->died)
                        <p class="wr-verwalten-hint mb-2">Dieser Held gilt derzeit als verschollen.</p>
                    @else
                        <p class="wr-verwalten-hint mb-2">Dieser Held ist im Register aktiv.</p>
                    @endif
                    <form method="POST" action="{{ route('heroes.missing', $hero) }}" data-refresh-modal>
                        @csrf @method('PATCH')
                        @if ($hero->died)
                            <button type="submit" class="ui small green button">
                                <i class="undo icon"></i> Als wiedergefunden markieren
                            </button>
                        @else
                            <button type="submit" class="ui small red button">
                                <i class="user times icon"></i> Als verschollen markieren
                            </button>
                        @endif
                    </form>
                </div>

            </div>
        </x-mobile.accordion-section>
        @endcan

    </div>

    {{-- Desktop: Fomantic-Tabs (sm+) --}}
    <div class="hidden sm:block">
        <div class="ui top attached tabular menu">
            <a class="item active" data-tab="overview" style="white-space: nowrap;">Übersicht</a>
            @if ($hero->classes->isNotEmpty())
                <a class="item" data-tab="skills" style="white-space: nowrap;">
                    Fertigkeiten
                    @if ($hero->classes_count > 1)
                        <span class="ui mini circular label ml-1">{{ $hero->classes_count }}</span>
                    @endif
                </a>
            @endif
            <a class="item" data-tab="adventures" style="white-space: nowrap;">Abenteuer</a>
            <a class="item" data-tab="ep" style="white-space: nowrap;">EP-Verlauf</a>
            @if ($hero->galleryImages->isNotEmpty() || $canEditPhoto)
            <a class="item" data-tab="gallery" style="white-space: nowrap;">
                <i class="images icon"></i> Galerie
                @if ($hero->galleryImages->isNotEmpty())
                    <span class="ui mini circular label ml-1">{{ $hero->galleryImages->count() }}</span>
                @endif
            </a>
            @endif
            @can('heldenregister.edit')
            <a class="item" data-tab="manage" style="white-space: nowrap;">Verwalten</a>
            @endcan
        </div>

        {{-- Tab: Übersicht --}}
        <div class="ui bottom attached tab segment active" data-tab="overview">
            @include('heroes.partials._overview', ['ctx' => 'd'])
        </div>

        {{-- Tab: Abenteuer --}}
        <div class="ui bottom attached tab segment" data-tab="adventures">
            @include('heroes.partials._adventures')
        </div>

        {{-- Tab: Fertigkeiten (alle Klassen zusammengefasst, UI-33) --}}
        @if ($hero->classes->isNotEmpty())
            <div class="ui bottom attached tab segment" data-tab="skills">
                @include('heroes.partials._skills', ['ctx' => 'd'])
            </div>
        @endif

        {{-- Tab: EP-Verlauf --}}
        <div class="ui bottom attached tab segment" data-tab="ep">
            @include('heroes.partials._ep', ['ctx' => 'd'])
        </div>

        {{-- Tab: Galerie (HERO-24) --}}
        @if ($hero->galleryImages->isNotEmpty() || $canEditPhoto)
        <div class="ui bottom attached tab segment" data-tab="gallery">
            @include('heroes.partials._gallery', ['ctx' => 'd'])
        </div>
        @endif

        {{-- Tab: Verwalten (nur Bürokrat / Admin) --}}
        @can('heldenregister.edit')
        <div class="ui bottom attached tab segment wr-verwalten-tab" data-tab="manage">
            <div class="wr-verwalten-grid">

                {{-- Helden-Siegel --}}
                <div class="wr-verwalten-panel wr-verwalten-siegel">
                    <h4 class="wr-verwalten-heading wr-verwalten-heading--lg">
                        <i class="id badge icon"></i> Helden-Siegel
                    </h4>
                    <form method="POST" action="{{ route('heroes.assign-code', $hero) }}" data-refresh-modal class="ui form">
                        @csrf @method('PATCH')
                        <div class="flex items-end gap-2 flex-wrap">
                            <div class="field !mb-0">
                                <input type="text" name="code" maxlength="6" minlength="6"
                                       pattern="[A-HJ-NP-Z2-9]{6}"
                                       placeholder="XXXXXX"
                                       value="{{ old('code', $hero->public_code) }}"
                                       class="font-mono tracking-widest w-28 siegel-input"
                                       style="text-transform:uppercase"
                                       required>
                            </div>
                            <button type="submit" class="ui primary small button">Siegel zuweisen</button>
                        </div>
                    </form>
                    @error('code') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    <p class="wr-verwalten-hint mt-1">6 Zeichen aus A–Z (kein I/L/O) und 2–9</p>
                    @if ($hero->public_code)
                        <div class="mt-3">
                            <a href="{{ route('admin.id-cards.reprint', $hero) }}" class="ui small basic button" target="_blank" rel="noopener">
                                <i class="print icon"></i> Ausweis drucken
                            </a>
                        </div>
                    @endif
                    @if ($hero->player)
                        {{-- Willkommens-Mail: eigener Unterbereich --}}
                        <div class="wr-verwalten-mail-box mt-4">
                            <p class="wr-verwalten-mail-label">
                                <i class="envelope icon"></i> Aufnahme-Bestätigung
                            </p>
                            <form method="POST" action="{{ route('heroes.welcome-mail', $hero) }}" data-refresh-modal>
                                @csrf
                                <button type="submit" class="ui small teal button">
                                    <i class="paper plane icon"></i> Willkommens-Mail senden
                                </button>
                            </form>
                            <p class="wr-verwalten-hint mt-1">Sendet die Aufnahme-Bestätigung an die Eltern/Betreuer des Spielers.</p>
                        </div>
                    @endif
                </div>

                {{-- Verschollen / Wiedergefunden --}}
                <div class="wr-verwalten-panel {{ $hero->died ? 'wr-verwalten-verschollen-aktiv' : 'wr-verwalten-verschollen-warnung' }}">
                    <h4 class="wr-verwalten-heading wr-verwalten-heading--lg">
                        <i class="user {{ $hero->died ? 'check circle' : 'times circle' }} icon"></i>
                        {{ $hero->died ? 'Held verschollen' : 'Held aktiv' }}
                    </h4>
                    @if ($hero->died)
                        <p class="wr-verwalten-hint mb-3">Dieser Held gilt derzeit als verschollen und ist nicht aktiv im Spiel.</p>
                    @else
                        <p class="wr-verwalten-hint mb-3">Dieser Held ist im Heldenregister aktiv und nimmt am Spielgeschehen teil.</p>
                    @endif
                    <form method="POST" action="{{ route('heroes.missing', $hero) }}" data-refresh-modal>
                        @csrf @method('PATCH')
                        @if ($hero->died)
                            <button type="submit" class="ui small green button">
                                <i class="undo icon"></i> Als wiedergefunden markieren
                            </button>
                        @else
                            <button type="submit" class="ui small red button">
                                <i class="user times icon"></i> Als verschollen markieren
                            </button>
                        @endif
                    </form>
                </div>

            </div>
        </div>
        @endcan

    </div>{{-- /hidden sm:block --}}

</div>{{-- /#skilltree --}}

<div data-modal-actions hidden>
    @can('heldenregister.edit')
        <button type="button" data-modal-url="{{ route('heroes.edit', $hero) }}" class="wr-btn wr-btn--primary">Bearbeiten</button>
    @endcan
</div>
</div>
