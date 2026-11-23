{{-- Waldritter v1: Schritt einer Aufgabenliste (z. B. Onboarding).
     Erledigte Schritte zeigen ein Häkchen; Beschreibung/Aktionen nur bei offenen Schritten.
     "laterHint" markiert Schritte, die erst später dran sind (helle Plakette + Hinweis),
     ohne den Text abzublenden – er bleibt voll lesbar. --}}
@props([
    'number',
    'title',
    'done' => false,
    'laterHint' => null,
])

<li {{ $attributes->class(['flex gap-3 items-start']) }}>
    @if ($done)
        <span class="wr-step__marker wr-step__marker--done" aria-hidden="true">&#10003;</span>
    @else
        <span @class(['wr-step__marker', 'wr-step__marker--later' => $laterHint]) aria-hidden="true">{{ $number }}</span>
    @endif

    <div class="flex-1 min-w-0">
        <p class="font-semibold m-0 {{ $done ? 'line-through text-wr-ink-muted' : '' }}">
            {{ $title }}
            @if ($done)
                <span class="sr-only">(erledigt)</span>
            @elseif ($laterHint)
                <span class="wr-step__later">{{ $laterHint }}</span>
            @endif
        </p>
        @unless ($done)
            <div class="mt-1 text-sm text-wr-ink-muted space-y-2">
                {{ $slot }}
            </div>
        @endunless
    </div>
</li>
