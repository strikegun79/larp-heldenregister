{{--
    SKILL-07: Spaltenansicht des Fertigkeitsbaums gruppiert nach Stufe (level).
    Erwartet: $class (HeroClass mit skills.prerequisites + skills.perlColor geladen),
              $learnedIds (Collection<int>).
--}}

@php
    $byLevel = $class->skills->sortBy(['level', 'name'])->groupBy('level');
    $levels  = $byLevel->keys()->sort()->values();
@endphp

@if ($byLevel->isEmpty())
    <p class="text-stone-500 text-sm">Für diese Klasse sind keine Fertigkeiten hinterlegt.</p>
@else
    <div class="overflow-x-auto pb-2">
        <div class="flex gap-3" style="min-width: max-content;">
            @foreach ($levels as $level)
                <div class="flex flex-col gap-2" style="min-width:11rem; max-width:14rem;">
                    {{-- Spaltenkopf --}}
                    <div class="text-xs font-semibold text-stone-400 uppercase tracking-wide
                                text-center border-b border-stone-200 pb-1 mb-0.5 sticky top-0 bg-white">
                        Stufe {{ $level ?: '?' }}
                    </div>

                    @foreach ($byLevel[$level] as $skill)
                        @php
                            $learned  = $learnedIds->contains($skill->id);
                            $missing  = $skill->prerequisites->filter(fn ($p) => ! $learnedIds->contains($p->id));
                            $locked   = ! $learned && $missing->isNotEmpty();
                        @endphp
                        <div class="rounded border px-3 py-2 text-sm cursor-pointer select-none skill-trigger
                                    {{ $learned
                                        ? 'bg-green-50 border-green-300 text-green-800'
                                        : ($locked
                                            ? 'bg-stone-50 border-stone-200 text-stone-400'
                                            : 'bg-amber-50 border-[#5a3a22]/30 text-waldritter hover:border-[#5a3a22] hover:bg-amber-100 transition-colors') }}"
                             data-skill-id="{{ $skill->id }}"
                             data-skill-name="{{ $skill->name }}"
                             data-skill-desc="{{ $skill->description }}"
                             data-skill-cost="{{ $skill->ep_costs }}"
                             data-skill-learned="{{ $learned ? 1 : 0 }}"
                             data-skill-locked="{{ $locked ? 1 : 0 }}"
                             data-skill-prereqs="{{ $locked ? $missing->pluck('name')->join(', ') : '' }}">

                            {{-- Perlenfarbe + Name --}}
                            <div class="flex items-start gap-1.5 leading-snug">
                                @if ($skill->perlColor)
                                    <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0 mt-0.5"
                                          style="background: {{ $skill->perlColor->code }}"
                                          title="{{ $skill->perlColor->name }}"></span>
                                @else
                                    <span class="inline-block w-2.5 h-2.5 shrink-0"></span>
                                @endif
                                <span class="font-medium break-words min-w-0">
                                    @if ($learned) ✓ @elseif ($locked) 🔒 @endif{{ $skill->name }}
                                </span>
                            </div>

                            {{-- EP + Status --}}
                            <div class="text-xs mt-1 {{ $learned ? 'text-green-600' : ($locked ? 'text-stone-400' : 'text-stone-500') }}">
                                {{ $skill->ep_costs }} EP
                                @if ($locked) · gesperrt
                                @elseif ($learned) · gelernt
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif
