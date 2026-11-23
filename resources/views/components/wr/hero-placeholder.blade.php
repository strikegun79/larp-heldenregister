{{-- Waldritter v1: Platzhalter für einen eigenen Spieler, der noch keinen Helden hat.
     Erklärt kindgerecht, dass der Held beim ersten Abenteuer entsteht. --}}
@props([
    'player',
    'self' => false,
])

<article {{ $attributes->merge(['class' => 'wr-card wr-card--placeholder']) }}>
    <div class="wr-card__body flex-row items-start gap-3 sm:gap-4">
        <span class="w-16 h-16 sm:w-20 sm:h-20 shrink-0 inline-flex items-center justify-center rounded-lg border-2 border-dashed border-wr-border bg-wr-parchment text-wr-leather"
              aria-hidden="true">
            <i class="question icon big m-0"></i>
        </span>

        <div class="flex-1 min-w-0 flex flex-col gap-1">
            <span class="wr-tag">{{ $self ? 'Dein Held' : 'Held von ' . $player->name }}</span>
            <h3 class="wr-card__title break-words hyphens-auto">Wartet noch auf das erste Abenteuer</h3>
            <p class="wr-card__text">
                {{ $self ? 'Dein Held entsteht' : 'Der Held entsteht' }} auf der ersten Veranstaltung –
                gemeinsam mit dem Bürokraten der Waldritter. Danach erscheint er hier.
            </p>

            @can('adventure.access')
                <div class="wr-card__actions">
                    <a href="{{ route('adventures.index') }}" class="wr-btn wr-btn--secondary wr-btn--small">
                        Abenteuer ansehen
                    </a>
                </div>
            @endcan
        </div>
    </div>
</article>
