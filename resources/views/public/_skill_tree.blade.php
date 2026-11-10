{{--
    PUB-11: Read-only Fertigkeitsbaum (Stufen-Spaltenansicht) für die öffentliche Heldenseite.
    Erwartet: $class (HeroClass mit skills.perlColor + skills.prerequisites geladen),
              $learnedIds (Collection<int>).
    Klick → dispatcht 'open-skill' an Alpine-Modal in hero.blade.php.
--}}

@php
    $byLevel = $class->skills->sortBy(['level', 'name'])->groupBy('level');
    $levels  = $byLevel->keys()->sort()->values();
    $earnedInClass = $class->skills->filter(fn ($s) => $learnedIds->contains($s->id));
@endphp

@if ($byLevel->isEmpty())
    <p class="text-stone-400 text-sm italic">Keine Fertigkeiten hinterlegt.</p>
@else
    <div class="overflow-x-auto pb-2">
        <div class="flex gap-3" style="min-width: max-content;">
            @foreach ($levels as $level)
                <div class="flex flex-col gap-2" style="min-width:10rem; max-width:13rem;">
                    <div class="text-xs font-semibold text-stone-400 uppercase tracking-wide
                                text-center border-b border-stone-200 pb-1">
                        Stufe {{ $level ?: '?' }}
                    </div>

                    @foreach ($byLevel[$level] as $skill)
                        @php($learned = $learnedIds->contains($skill->id))
                        @php($missing = ($prereqMap->get("{$class->id}_{$skill->id}") ?? collect())->filter(fn ($r) => ! $learnedIds->contains($r->req_id)))
                        @php($locked  = ! $learned && $missing->isNotEmpty())
                        <button type="button"
                                class="rounded border px-2.5 py-1.5 text-sm text-left w-full
                                    cursor-pointer transition-colors hover:brightness-95
                                    {{ $learned ? 'bg-green-50 border-green-300 text-green-800'
                                                : ($locked ? 'bg-stone-100 border-stone-300 text-stone-400'
                                                           : 'bg-stone-50 border-stone-200 text-stone-600') }}"
                                data-skill-name="{{ $skill->name }}"
                                data-skill-desc="{{ $skill->description ?? '' }}"
                                data-skill-cost="{{ $skill->ep_costs }}"
                                data-skill-learned="{{ $learned ? '1' : '0' }}"
                                data-skill-locked="{{ $locked ? '1' : '0' }}"
                                data-skill-prereqs="{{ $locked ? $missing->pluck('req_name')->join(', ') : '' }}"
                                x-on:click="$dispatch('open-skill', {
                                    name:    $el.dataset.skillName,
                                    desc:    $el.dataset.skillDesc,
                                    cost:    $el.dataset.skillCost,
                                    learned: $el.dataset.skillLearned === '1',
                                    locked:  $el.dataset.skillLocked  === '1',
                                    prereqs: $el.dataset.skillPrereqs
                                })"
                                title="{{ $skill->name }}{{ $locked ? ' – gesperrt' : '' }}">
                            <div class="flex items-start gap-1.5 leading-snug">
                                @if ($skill->perlColor)
                                    <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0 mt-0.5"
                                          style="background:{{ $skill->perlColor->code }}"
                                          title="{{ $skill->perlColor->name }}"></span>
                                @else
                                    <span class="inline-block w-2.5 h-2.5 shrink-0"></span>
                                @endif
                                <span class="font-medium break-words min-w-0">
                                    @if ($learned) ✓ @endif{{ $skill->name }}
                                </span>
                            </div>
                            <div class="text-xs mt-0.5
                                {{ $learned ? 'text-green-600' : ($locked ? 'text-stone-400' : 'text-stone-400') }}">
                                {{ $skill->ep_costs }} EP
                                @if ($learned) · gelernt
                                @elseif ($locked) · gesperrt
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    @if ($earnedInClass->isEmpty())
        <p class="text-stone-400 text-sm italic mt-2">Noch keine Fertigkeiten in dieser Klasse erlernt.</p>
    @endif
@endif
