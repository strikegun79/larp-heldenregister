{{-- Abenteuer-Kurzinfo über den Buchungsformularen (Waldritter v1, UI-31):
     Name, Termin mit Wochentag/Uhrzeit, Ort, Beitrag, Anmeldestatus, freie Plätze. Erwartet: $adventure. --}}
<div class="wr-card p-4 mb-4">
    <p class="wr-card__title text-lg mb-2">{{ $adventure->name }}</p>
    <x-wr.adventure-meta :adventure="$adventure" class="text-wr-ink-muted mb-2" />
    <div class="flex flex-wrap gap-1.5">
        <x-wr.adventure-status :adventure="$adventure" :show-slots="false" />
        @if ($adventure->max_player > 0)
            <span class="wr-pill"><i class="users icon" aria-hidden="true"></i>Noch {{ $adventure->freeSlots() }} von {{ $adventure->max_player }} frei</span>
        @endif
        @if ($adventure->fee > 0 && $adventure->fee_reduced !== null)
            <span class="wr-pill">ermäßigt {{ number_format($adventure->fee_reduced, 2, ',', '.') }} €</span>
        @endif
    </div>
</div>
