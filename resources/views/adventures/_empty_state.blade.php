{{-- Leerzustand der Abenteuerliste (Waldritter v1). Bei aktiver Suche Hinweis auf „Zurücksetzen“. --}}
<div class="wr-card p-8 text-center items-center">
    @if (! empty($q))
        <p class="font-semibold m-0 mb-1">Kein Abenteuer zu „{{ $q }}“ gefunden.</p>
        <p class="wr-card__text mb-4">Probiere einen anderen Suchbegriff oder zeige wieder alle Abenteuer.</p>
        <a href="{{ route('adventures.index', ['sort' => $sort ?? 'grouped']) }}" class="wr-btn wr-btn--secondary">Alle Abenteuer zeigen</a>
    @else
        <p class="font-semibold m-0 mb-1">Keine Abenteuer geplant.</p>
        <p class="wr-card__text mb-4">Sobald ein Abenteuer veröffentlicht wird, erscheint es hier.</p>
        @can('events.edit')
            <a href="{{ route('adventures.manage-index') }}" class="wr-btn wr-btn--primary">Zur Verwaltung</a>
        @endcan
    @endif
</div>
