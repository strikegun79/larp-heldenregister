{{--
    Öffentlicher Klassen-Fertigkeitsbaum als Bildansicht (read-only).
    Erwartet: $class (HeroClass mit skills.perlColor + skills.prerequisites geladen),
              $learnedIds (Collection<int>).
    Klick → dispatcht 'open-skill' an Alpine-Modal in hero.blade.php.
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
            @php($missing  = ($prereqMap->get("{$class->id}_{$skill->id}") ?? collect())->filter(fn ($r) => ! $learnedIds->contains($r->req_id)))
            @php($locked   = ! $learned && $missing->isNotEmpty())
            <button type="button"
                    class="skill-marker skill-trigger {{ $learned ? 'learned' : ($locked ? 'locked' : 'unlearned') }}"
                    style="left:{{ $px }}%; top:{{ $py }}%;"
                    title="{{ $skill->name }} ({{ $skill->ep_costs }} EP){{ $locked ? ' – gesperrt' : '' }}"
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
                    aria-label="{{ $skill->name }}{{ $learned ? ' – gelernt' : ($locked ? ' – gesperrt' : '') }}">
            </button>
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
            <span class="inline-block w-3 h-3 rounded-full bg-stone-300 border border-stone-400"></span> Gesperrt
        </span>
    </div>
@endif
