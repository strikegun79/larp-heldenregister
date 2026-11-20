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
            @include('heroes.partials._manage', ['ctx' => 'm'])
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
        <div class="ui bottom attached tab segment" data-tab="manage">
            @include('heroes.partials._manage', ['ctx' => 'd'])
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
