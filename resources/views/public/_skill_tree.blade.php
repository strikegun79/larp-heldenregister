{{--
    PUB-11: Read-only Fertigkeitsbaum für die öffentliche Heldenseite.
    Erwartet: $class (HeroClass mit skills.perlColor geladen),
              $learnedIds (Collection<int>).
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
                        <div class="rounded border px-2.5 py-1.5 text-sm
                            {{ $learned
                                ? 'bg-green-50 border-green-300 text-green-800'
                                : 'bg-stone-50 border-stone-200 text-stone-400' }}">
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
                            @if ($learned)
                                <div class="text-xs mt-0.5 text-green-600">
                                    {{ $skill->ep_costs }} EP · gelernt
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    @if ($earnedInClass->isEmpty())
        <p class="text-stone-400 text-sm italic mt-2">Noch keine Fertigkeiten in dieser Klasse erlernt.</p>
    @endif
@endif
