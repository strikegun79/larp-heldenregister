{{-- Issue #29: Helden-Schwur der Klasse – Button + Fenster für den Bürokraten.
     Erwartet: $hero, $class, $learnedIds, $ctx (m/d, da das Heldenbuch für Handy und Desktop doppelt gerendert wird). --}}
@can('heroes.oath')
    @if ($class->hasOath())
        @php
            $oathId = "oath-{$ctx}-{$class->id}";
            $learnedNames = \App\Models\Skill::whereIn('id', $learnedIds)->pluck('name');
            $missingSkills = collect(\App\Models\HeroClass::OATH_REQUIRED_SKILLS)
                ->reject(fn ($name) => $learnedNames->contains($name));
            $oathLabel = $class->oath_title ?: 'Helden-Schwur';
        @endphp

        <button type="button" class="wr-btn wr-btn--secondary wr-btn--small mb-3"
                aria-haspopup="dialog" aria-controls="{{ $oathId }}"
                onclick="document.getElementById('{{ $oathId }}').showModal()">
            📜 {{ $oathLabel }}
        </button>

        <dialog id="{{ $oathId }}" aria-labelledby="{{ $oathId }}-title"
                class="rounded-lg backdrop:bg-black/50 border-2 border-[#5a3a22]/50 bg-[#fbf6ea] p-0 shadow-2xl w-[min(42rem,calc(100vw-2rem))] max-h-[90dvh]">
            <div class="p-6 overflow-y-auto">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-500 m-0">{{ $class->name }} · {{ $hero->character_name }}</p>
                        <h2 id="{{ $oathId }}-title" class="font-uncial text-2xl text-waldritter m-0">{{ $oathLabel }}</h2>
                    </div>
                    <form method="dialog">
                        <button type="submit" class="wr-btn wr-btn--secondary wr-btn--small !px-3" aria-label="Schwur schließen">
                            <i class="close icon m-0" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>

                {{-- Voraussetzung für die Bändchen-Übergabe --}}
                @if ($missingSkills->isEmpty())
                    <p class="rounded border border-green-300 bg-green-50 text-green-900 px-4 py-2 text-sm mb-5">
                        ✓ {{ implode(' und ', \App\Models\HeroClass::OATH_REQUIRED_SKILLS) }} erlernt – das Bändchen kann übergeben werden.
                    </p>
                @else
                    <p class="rounded border border-amber-300 bg-amber-50 text-amber-900 px-4 py-2 text-sm mb-5">
                        ⚠ Vor der Bändchen-Übergabe fehlt noch: <strong>{{ $missingSkills->implode(', ') }}</strong>.
                    </p>
                @endif

                <section class="mb-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500 mb-2">Schwur – vom Bürokraten vorgesagt</h3>
                    <div class="text-xl leading-relaxed text-stone-900 whitespace-pre-line">{{ $class->oath_text }}</div>
                </section>

                @if (filled($class->ceremony_text))
                    <section class="border-t border-[#5a3a22]/20 pt-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500 mb-2">Feierliche Aufnahme – Spielleitung</h3>
                        <div class="text-lg leading-relaxed italic text-stone-800 whitespace-pre-line">{{ $class->ceremony_text }}</div>
                    </section>
                @endif
            </div>
        </dialog>
    @endif
@endcan
