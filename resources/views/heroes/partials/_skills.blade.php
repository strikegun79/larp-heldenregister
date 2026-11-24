{{-- Heldenbuch – Fertigkeiten (Waldritter v1). Einmal geschrieben für Akkordeon und Reiter.
     Zustände learned / unlearned / locked bleiben (Farbe) und werden durch Symbol und
     zugängliche Beschriftung ergänzt. Alle Auslöser sind echte Buttons; die data-skill-*-Attribute
     werden vom Lern-Dialog (resources/js/heldenregister.js, .skill-trigger) gelesen.
     Erwartet: $hero, $learnedIds, $prereqMap, $ctx. --}}
<div x-data="{ activeClass: {{ $hero->classes->first()->id }}, view: 'tree' }" class="space-y-4">

    <div class="flex flex-wrap items-center gap-3">
        {{-- Klassenwahl (nur bei mehreren Klassen) --}}
        @if ($hero->classes->count() > 1)
            <div class="wr-segmented" role="group" aria-label="Klasse wählen">
                @foreach ($hero->classes as $class)
                    <button type="button" @click="activeClass = {{ $class->id }}"
                            :aria-pressed="(activeClass === {{ $class->id }}).toString()"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                        {{ $class->name }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- SKILL-07: Ansicht Baum / Stufen --}}
        <div class="wr-segmented" role="group" aria-label="Ansicht wählen">
            <button type="button" @click="view = 'tree'" :aria-pressed="(view === 'tree').toString()" aria-pressed="true">
                <i class="sitemap icon m-0" aria-hidden="true"></i> Baum
            </button>
            <button type="button" @click="view = 'columns'" :aria-pressed="(view === 'columns').toString()" aria-pressed="false">
                <i class="columns icon m-0" aria-hidden="true"></i> Stufen
            </button>
        </div>
    </div>

    {{-- Legende: Zustand nie nur über Farbe --}}
    <ul class="flex flex-wrap gap-x-4 gap-y-1 m-0 p-0 list-none text-sm text-wr-ink-muted" role="list" aria-label="Legende">
        <li class="inline-flex items-center gap-1.5"><span class="wr-skill-legend wr-skill-legend--learned" aria-hidden="true">✓</span> erlernt</li>
        <li class="inline-flex items-center gap-1.5"><span class="wr-skill-legend wr-skill-legend--unlearned" aria-hidden="true"></span> kann gelernt werden</li>
        <li class="inline-flex items-center gap-1.5"><span class="wr-skill-legend wr-skill-legend--locked" aria-hidden="true"><i class="lock icon m-0"></i></span> gesperrt (erst Voraussetzungen lernen)</li>
    </ul>

    @foreach ($hero->classes as $class)
        <div x-show="activeClass === {{ $class->id }}" @unless ($loop->first) style="display: none" @endunless>
            @if ($hero->classes->count() === 1)
                <h3 class="wr-section-heading__title text-lg mb-3">{{ $class->name }}</h3>
            @endif

            {{-- Issue #29: Helden-Schwur der Klasse (nur Bürokrat) --}}
            @include('heroes.partials._oath')

            {{-- Baum-Ansicht (Bild mit Markern) + Liste --}}
            <div x-show="view === 'tree'">
                <div class="skill-map">
                    <img src="{{ $class->skilltreeImage() }}" alt="Fertigkeitsbaum {{ $class->name }}" class="skill-image" loading="lazy">
                    @foreach ($class->skills as $skill)
                        @php
                            $learned = $learnedIds->contains($skill->id);
                            $unset = ($skill->pivot->x_percentage == 0 && $skill->pivot->y_percentage == 0);
                            $px = $unset ? 6 + ($loop->index % 10) * 9 : (int) $skill->pivot->x_percentage;
                            $py = $unset ? 8 + intdiv($loop->index, 10) * 11 : (int) $skill->pivot->y_percentage;
                            $missingPrereqs = ($prereqMap->get("{$class->id}_{$skill->id}") ?? collect())->filter(fn ($r) => ! $learnedIds->contains($r->req_id));
                            $locked = ! $learned && $missingPrereqs->isNotEmpty();
                            $stateText = $learned ? 'erlernt' : ($locked ? 'gesperrt' : 'noch nicht erlernt');
                        @endphp
                        <button type="button"
                                class="skill-marker skill-trigger {{ $learned ? 'learned' : ($locked ? 'locked' : 'unlearned') }}"
                                style="left: {{ $px }}%; top: {{ $py }}%;"
                                title="{{ $skill->name }} ({{ $skill->ep_costs }} EP){{ $locked ? ' – gesperrt' : '' }}"
                                aria-label="{{ $skill->name }}, {{ $skill->ep_costs }} EP, {{ $stateText }}"
                                data-skill-id="{{ $skill->id }}"
                                data-skill-name="{{ $skill->name }}"
                                data-skill-desc="{{ $skill->description }}"
                                data-skill-cost="{{ $skill->ep_costs }}"
                                data-skill-perl-name="{{ $skill->perlColor?->name }}"
                                data-skill-perl-code="{{ $skill->perlColor?->code }}"
                                data-skill-learned="{{ $learned ? 1 : 0 }}"
                                data-skill-locked="{{ $locked ? 1 : 0 }}"
                                data-skill-prereqs="{{ $locked ? $missingPrereqs->pluck('req_name')->join(', ') : '' }}"></button>
                    @endforeach
                </div>

                @if ($class->skills->isEmpty())
                    <p class="text-wr-ink-muted text-sm mt-3">Für diese Klasse sind keine Fertigkeiten hinterlegt.</p>
                @else
                    <ul class="wr-skill-list" role="list">
                        @foreach ($class->skills as $skill)
                            @php
                                $learned = $learnedIds->contains($skill->id);
                                $missingPrereqs = ($prereqMap->get("{$class->id}_{$skill->id}") ?? collect())->filter(fn ($r) => ! $learnedIds->contains($r->req_id));
                                $locked = ! $learned && $missingPrereqs->isNotEmpty();
                            @endphp
                            <li>
                                <button type="button"
                                        class="skill-trigger wr-skill-item {{ $learned ? 'wr-skill-item--learned' : ($locked ? 'wr-skill-item--locked' : '') }}"
                                        data-skill-id="{{ $skill->id }}"
                                        data-skill-name="{{ $skill->name }}"
                                        data-skill-desc="{{ $skill->description }}"
                                        data-skill-cost="{{ $skill->ep_costs }}"
                                        data-skill-perl-name="{{ $skill->perlColor?->name }}"
                                        data-skill-perl-code="{{ $skill->perlColor?->code }}"
                                        data-skill-learned="{{ $learned ? 1 : 0 }}"
                                        data-skill-locked="{{ $locked ? 1 : 0 }}"
                                        data-skill-prereqs="{{ $locked ? $missingPrereqs->pluck('req_name')->join(', ') : '' }}">
                                    @if ($learned)
                                        <i class="check circle icon m-0" aria-hidden="true"></i>
                                    @elseif ($locked)
                                        <i class="lock icon m-0" aria-hidden="true"></i>
                                    @else
                                        <span class="inline-block w-[1.18em]" aria-hidden="true"></span>
                                    @endif
                                    <span class="flex-1 min-w-0 break-words">{{ $skill->name }}</span>
                                    <span class="shrink-0 text-sm">{{ $skill->ep_costs }} EP</span>
                                    <span class="shrink-0 text-sm">{{ $learned ? 'erlernt' : ($locked ? 'gesperrt' : '') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- SKILL-07: Spalten-Ansicht nach Stufe --}}
            <div x-show="view === 'columns'" style="display: none">
                @include('heroes.partials._skill_columns', ['class' => $class, 'learnedIds' => $learnedIds, 'prereqMap' => $prereqMap])
            </div>

            @can('heldenregister.edit')
                <div class="mt-3">
                    <a href="{{ route('skilltree.edit', $class) }}" class="wr-btn wr-btn--secondary wr-btn--small">Positionen bearbeiten</a>
                </div>
            @endcan
        </div>
    @endforeach
</div>
