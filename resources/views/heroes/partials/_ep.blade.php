{{-- Heldenbuch – EP-Verlauf (Waldritter v1). Einmal geschrieben für Akkordeon und Reiter.
     Plus/Minus zusätzlich als Text („gutgeschrieben“/„ausgegeben“), nicht nur farbig.
     Erwartet: $hero, $epTypes, $ctx (eindeutige IDs). --}}
<div class="space-y-4">
    <p class="text-sm text-wr-ink-muted m-0">
        Hier siehst du alle Buchungen deiner Erfahrungspunkte – zum Beispiel nach einem Abenteuer
        oder wenn du eine Fertigkeit gelernt hast.
    </p>

    <a href="{{ route('heroes.ep.export', $hero) }}" target="_blank" rel="noopener" class="wr-btn wr-btn--secondary wr-btn--small">
        <i class="download icon m-0" aria-hidden="true"></i> EP-Auszug (CSV)
    </a>

    @can('heldenregister.edit')
        {{-- EP buchen (Team) --}}
        <form method="POST" action="{{ route('heroes.ep.store', $hero) }}" class="ui form wr-card p-3" data-refresh-modal>
            @csrf
            @php($firstDefault = $epTypes->first()?->default_ep)
            <div class="flex flex-wrap items-end gap-3">
                {{-- Issue #28: Erst die Buchungsart, dann die EP; der Standardwert der Art wird vorbelegt --}}
                <div class="field m-0 flex-1 min-w-[12rem]">
                    <label for="ep-type-{{ $ctx }}">Grund</label>
                    <select id="ep-type-{{ $ctx }}" name="ep_transaction_type_id" required
                            data-ep-type-select data-ep-count-target="ep-count-{{ $ctx }}">
                        @foreach ($epTypes as $type)
                            <option value="{{ $type->id }}" data-default-ep="{{ $type->default_ep }}">
                                {{ $type->description }} ({{ $type->is_credit ? '+' : '−' }}{{ $type->default_ep ? ', Standard '.$type->default_ep.' EP' : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field m-0">
                    <label for="ep-count-{{ $ctx }}">EP</label>
                    <input id="ep-count-{{ $ctx }}" type="number" name="ep_count" step="1" min="1" placeholder="Anzahl" required style="width:7rem"
                           value="{{ $firstDefault }}" @if ($firstDefault) data-ep-auto="{{ $firstDefault }}" @endif>
                </div>
                <div class="field m-0">
                    <label for="ep-date-{{ $ctx }}">Datum</label>
                    <input id="ep-date-{{ $ctx }}" type="date" name="transacted_at" style="width:10rem">
                </div>
                <button type="submit" class="wr-btn wr-btn--primary">Buchen</button>
            </div>
        </form>
    @endcan

    @php($transactions = $hero->epTransactions->sortByDesc('transacted_at'))
    @if ($transactions->isEmpty())
        <p class="text-wr-ink-muted m-0">Keine EP-Buchungen.</p>
    @else
        <ul class="wr-rows" role="list">
            @foreach ($transactions as $tx)
                @php($credit = (bool) $tx->type?->is_credit)
                <li class="wr-row">
                    <div class="min-w-0">
                        <span class="font-semibold break-words">{{ $tx->type?->description }}</span>
                        @if ($tx->adventure)
                            <span class="text-sm text-wr-ink-muted"> · {{ $tx->adventure->name }}</span>
                        @endif
                        <span class="block text-sm text-wr-ink-muted">{{ optional($tx->transacted_at)->format('d.m.Y') }}</span>
                    </div>
                    <span class="wr-pill {{ $credit ? 'wr-pill--success' : 'wr-pill--pending' }} shrink-0">
                        {{ $credit ? '+' : '−' }}{{ number_format($tx->ep_count, 0, ',', '.') }} EP {{ $credit ? 'gutgeschrieben' : 'ausgegeben' }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
