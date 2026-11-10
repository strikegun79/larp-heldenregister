@php
    $displayName = filled($hero->character_name)
        ? $hero->character_name
        : mb_strtoupper(mb_substr($hero->player->name, 0, 1))
          . mb_strtoupper(mb_substr($hero->player->lastname, 0, 1));
    $isInitials = ! filled($hero->character_name);
@endphp

<x-public-layout :title="$displayName . ' – Heldenregister'">

{{-- PUB-12: Alpine-Wrapper für Read-only Skill-Modal --}}
<div x-data="{
        showSkillModal: false,
        skill: { name:'', desc:'', cost:0, learned:false, locked:false, prereqs:'' }
     }"
     @open-skill.window="skill = $event.detail; showSkillModal = true"
     @keydown.escape.window="showSkillModal = false">

    <div class="max-w-2xl mx-auto px-4 sm:px-6">

        {{-- Helden-Header --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden mb-6">
            <div class="flex items-center gap-5 p-5">
                <img src="{{ $hero->image_url }}" alt="{{ $displayName }}"
                     class="h-24 w-24 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0" loading="lazy">
                <div class="flex-1 min-w-0">
                    <h1 class="font-uncial text-2xl text-waldritter leading-tight">
                        {{ $displayName }}
                        @if ($isInitials)
                            <span class="text-base text-stone-400 font-normal">(noch namenlos)</span>
                        @endif
                    </h1>
                    @if ($hero->classes->isNotEmpty())
                        <p class="text-stone-600 mt-1">
                            {{ $hero->classes->pluck('name')->implode(', ') }}
                        </p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-1.5 items-center">
                        @if ($hero->died)
                            <span class="ui tiny red label">verschollen</span>
                        @elseif ($hero->active)
                            <span class="ui tiny green label">aktiv</span>
                        @else
                            <span class="ui tiny label">inaktiv</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Eckdaten --}}
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 px-5 pb-5 text-sm">
                <div>
                    <dt class="text-stone-500">Erblickung</dt>
                    <dd class="text-stone-800 font-medium">
                        {{ $hero->born ? $hero->born->locale('de')->isoFormat('D. MMMM YYYY') : 'Unbekannt' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-stone-500">Heimatort</dt>
                    <dd class="text-stone-800 font-medium">
                        {{ filled($hero->homeplace) ? $hero->homeplace : 'Unbekannt' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-stone-500">Verfügbare EP</dt>
                    <dd class="text-stone-800 font-medium">{{ number_format($hero->ep_balance, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-stone-500">Erlernte Fertigkeiten</dt>
                    <dd class="text-stone-800 font-medium">{{ $hero->skills_count }}</dd>
                </div>
            </dl>
        </div>

        {{-- Steckbrief --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
            <h2 class="font-uncial text-lg text-waldritter mb-2">Steckbrief</h2>
            @if (filled($hero->description_plain))
                <div class="wr-steckbrief text-stone-700 break-words">{!! $hero->description_html !!}</div>
            @else
                <p class="text-stone-400 italic">Noch keine Eintragungen</p>
            @endif
        </div>

        {{-- Fertigkeitsbäume mit Tab-Toggle (Baum / Stufen) --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
            <h2 class="font-uncial text-lg text-waldritter mb-4">Fertigkeiten</h2>
            @if ($hero->classes->isEmpty())
                <p class="text-stone-400 italic">Noch keine Eintragungen</p>
            @else
                @foreach ($hero->classes as $class)
                    <div x-data="{ view: 'baum' }"
                         class="{{ ! $loop->first ? 'mt-6 pt-6 border-t border-stone-200' : '' }}">

                        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                            <h3 class="font-semibold text-waldritter flex items-center gap-2">
                                {{ $class->name }}
                                @php($classLearnedCount = $class->skills->filter(fn ($s) => $learnedIds->contains($s->id))->count())
                                @if ($classLearnedCount > 0)
                                    <span class="ui mini green circular label">{{ $classLearnedCount }}</span>
                                @endif
                            </h3>

                            {{-- Tab-Schalter --}}
                            <div class="flex gap-1.5">
                                <button type="button"
                                        @click="view = 'baum'"
                                        :class="view === 'baum' ? 'ui mini primary button' : 'ui mini basic button'">
                                    <i class="sitemap icon"></i> Skilltree
                                </button>
                                <button type="button"
                                        @click="view = 'stufen'"
                                        :class="view === 'stufen' ? 'ui mini primary button' : 'ui mini basic button'">
                                    <i class="columns icon"></i> Skill-Stufen
                                </button>
                            </div>
                        </div>

                        {{-- Skilltree: Bildansicht --}}
                        <div x-show="view === 'baum'" x-cloak>
                            @include('public._skill_tree_image', ['class' => $class, 'learnedIds' => $learnedIds, 'prereqMap' => $prereqMap])
                        </div>

                        {{-- Skill-Stufen: Spaltenansicht --}}
                        <div x-show="view === 'stufen'" x-cloak>
                            @include('public._skill_tree', ['class' => $class, 'learnedIds' => $learnedIds, 'prereqMap' => $prereqMap])
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Bändchen & Perlen (je Klasse mit Bandstreifen) --}}
        @php($perlByClass = $hero->perlSummaryByClass)
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
            <h2 class="font-uncial text-lg text-waldritter mb-3">Bändchen &amp; Perlen</h2>
            @if ($perlByClass->isEmpty())
                <p class="text-stone-400 italic">Noch keine Eintragungen</p>
            @else
                @include('partials._ribbon_perls', ['perlByClass' => $perlByClass, 'compact' => false])
            @endif
        </div>

        {{-- Gruppen --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
            <h2 class="font-uncial text-lg text-waldritter mb-3">Gruppen</h2>
            @if ($hero->groups->isEmpty())
                <p class="text-stone-400 italic">Noch keine Eintragungen</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($hero->groups as $group)
                        <span class="ui label">
                            {{ $group->name }}
                            @if ($group->pivot->role)
                                <span class="detail">{{ $group->pivot->role }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Galerie (HERO-24) --}}
        @if ($hero->galleryImages->isNotEmpty())
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
            <h2 class="font-uncial text-lg text-waldritter mb-3">Galerie</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($hero->galleryImages as $img)
                    <img src="{{ $img->url }}" alt="Galerie-Bild"
                         class="w-full h-auto rounded border border-stone-200" loading="lazy">
                @endforeach
            </div>
        </div>
        @endif

        {{-- Teilen --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6 text-center">
            <p class="text-sm text-stone-600 mb-2">
                Helden-Siegel:
                <code class="font-mono tracking-widest text-waldritter text-base">{{ $hero->public_code }}</code>
            </p>
            <p class="text-xs text-stone-400 mb-3 break-all">{{ url()->current() }}</p>
            <button type="button"
                    class="ui small button"
                    onclick="navigator.clipboard.writeText('{{ url()->current() }}').then(function(){this.textContent='✓ Kopiert!';setTimeout(function(){document.querySelectorAll('[data-copy-btn]').forEach(function(b){b.textContent='Link kopieren'})},2000)}.bind(this))"
                    data-copy-btn>
                Link kopieren
            </button>
        </div>

    </div>

    {{-- Read-only Skill-Info-Modal --}}
    <div x-show="showSkillModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.55);"
         @click.self="showSkillModal = false"
         role="dialog" aria-modal="true">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm border-2 border-[#5a3a22]/30 overflow-hidden">
            {{-- Header --}}
            <div class="flex items-start justify-between bg-[#f5e9d0] border-b border-[#5a3a22]/20 px-5 py-4">
                <h3 class="font-uncial text-lg text-waldritter leading-tight pr-4" x-text="skill.name"></h3>
                <button type="button" @click="showSkillModal = false"
                        class="text-stone-400 hover:text-waldritter shrink-0 mt-0.5"
                        aria-label="Schließen">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            {{-- Body --}}
            <div class="px-5 py-4 space-y-3">
                <p class="text-stone-700 text-sm" x-text="skill.desc || '(Keine Beschreibung hinterlegt.)'"></p>
                <p class="text-sm text-stone-500">
                    Kosten: <strong class="text-stone-800" x-text="skill.cost + ' EP'"></strong>
                </p>
                <template x-if="skill.learned">
                    <p class="text-green-700 font-medium text-sm">✓ Diese Fertigkeit wurde erlernt.</p>
                </template>
                <template x-if="skill.locked && !skill.learned">
                    <p class="text-amber-700 text-sm">
                        🔒 Gesperrt – fehlende Voraussetzungen:
                        <span class="font-medium" x-text="skill.prereqs"></span>
                    </p>
                </template>
            </div>
            {{-- Footer --}}
            <div class="px-5 py-3 bg-stone-50 border-t border-stone-200 text-right">
                <button type="button" @click="showSkillModal = false"
                        class="ui small button">Schließen</button>
            </div>
        </div>
    </div>

</div>{{-- Ende Alpine-Wrapper --}}

</x-public-layout>
