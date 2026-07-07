{{--
    Öffentlicher Klassen-Fertigkeitsbaum als Bildansicht (read-only).
    Erwartet: $class (HeroClass mit skills.perlColor + skills.prerequisites geladen),
              $learnedIds (Collection<int>).
--}}
@if ($class->skills->isEmpty())
    <p class="text-stone-400 text-sm italic">Keine Fertigkeiten hinterlegt.</p>
@else
    <div class="skill-map">
        <img src="{{ $class->skilltreeImage() }}"
             alt="Fertigkeitsbaum {{ $class->name }}"
             class="skill-image" loading="lazy">

        @foreach ($class->skills as $skill)
            @php($learned  = $learnedIds->contains($skill->id))
            @php($unset    = ($skill->pivot->x_percentage == 0 && $skill->pivot->y_percentage == 0))
            @php($px       = $unset ? 6 + ($loop->index % 10) * 9 : (int) $skill->pivot->x_percentage)
            @php($py       = $unset ? 8 + intdiv($loop->index, 10) * 11 : (int) $skill->pivot->y_percentage)
            @php($missing  = $skill->prerequisites->filter(fn ($p) => ! $learnedIds->contains($p->id)))
            @php($locked   = ! $learned && $missing->isNotEmpty())
            <span class="skill-marker {{ $learned ? 'learned' : ($locked ? 'locked' : 'unlearned') }}"
                  style="left:{{ $px }}%; top:{{ $py }}%;"
                  title="{{ $skill->name }} ({{ $skill->ep_costs }} EP){{ $locked ? ' – gesperrt' : '' }}"
                  aria-label="{{ $skill->name }}{{ $learned ? ' – gelernt' : ($locked ? ' – gesperrt' : '') }}"></span>
        @endforeach
    </div>

    {{-- Legende --}}
    <div class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-xs text-stone-500">
        <span class="flex items-center gap-1">
            <span class="inline-block w-3 h-3 rounded-full bg-green-400 border border-green-600"></span> Gelernt
        </span>
        <span class="flex items-center gap-1">
            <span class="inline-block w-3 h-3 rounded-full bg-amber-200 border border-[#5a3a22]/40"></span> Verfügbar
        </span>
        <span class="flex items-center gap-1">
            <span class="inline-block w-3 h-3 rounded-full bg-stone-200 border border-stone-300"></span> Gesperrt
        </span>
    </div>
@endif
