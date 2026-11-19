{{-- Waldritter v1: Schritt einer Aufgabenliste (z. B. Onboarding).
     Erledigte Schritte zeigen ein Häkchen; Beschreibung/Aktionen nur bei offenen Schritten. --}}
@props([
    'number',
    'title',
    'done' => false,
    'muted' => false,
])

<li {{ $attributes->class(['flex gap-3 items-start', 'opacity-60' => $muted]) }}>
    @if ($done)
        <span class="wr-step__marker wr-step__marker--done" aria-hidden="true">&#10003;</span>
    @else
        <span class="wr-step__marker" aria-hidden="true">{{ $number }}</span>
    @endif

    <div class="flex-1 min-w-0">
        <p class="font-semibold m-0 {{ $done ? 'line-through text-wr-ink-muted' : '' }}">
            {{ $title }}
            @if ($done)
                <span class="sr-only">(erledigt)</span>
            @endif
        </p>
        @unless ($done)
            <div class="mt-1 text-sm text-wr-ink-muted space-y-2">
                {{ $slot }}
            </div>
        @endunless
    </div>
</li>
