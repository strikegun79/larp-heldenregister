{{-- Spieler-Detail – besuchte Abenteuer (Waldritter v1). Erwartet: $player. --}}
@php($visited = $player->visits->filter(fn ($v) => $v->adventure)->sortByDesc(fn ($v) => optional($v->adventure)->start_at))

@if ($visited->isEmpty())
    <p class="text-wr-ink-muted m-0">Noch keine besuchten Veranstaltungen.</p>
@else
    <ul class="wr-rows" role="list">
        @foreach ($visited as $visit)
            <li class="wr-row">
                <div class="min-w-0">
                    <a href="{{ route('adventures.show', $visit->adventure) }}" data-modal-url="{{ route('adventures.show', $visit->adventure) }}"
                       class="wr-interactive font-semibold text-wr-forest underline break-words">{{ $visit->adventure->name }}</a>
                    <span class="block text-sm text-wr-ink-muted">
                        @if ($visit->adventure->start_at)
                            <x-wr.adventure-date :adventure="$visit->adventure" />
                        @else
                            Termin unbekannt
                        @endif
                    </span>
                </div>
            </li>
        @endforeach
    </ul>
@endif
