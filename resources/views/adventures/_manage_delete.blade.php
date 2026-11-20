{{-- Löschen in der Verwaltungsliste: mit Rückfrage (data-confirm); gesperrt mit sichtbarem Grund.
     Erwartet $adventure, $blockReason (Closure), $idSuffix. --}}
@php($reason = $blockReason($adventure))
@if ($reason === null)
    <form method="POST" action="{{ route('adventures.destroy', $adventure) }}" class="inline"
          data-confirm="Abenteuer „{{ $adventure->name }}“ wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.">
        @csrf
        @method('DELETE')
        <button type="submit" class="wr-btn wr-btn--danger wr-btn--small" aria-label="Abenteuer {{ $adventure->name }} löschen">
            <i class="trash icon m-0" aria-hidden="true"></i> Löschen
        </button>
    </form>
@else
    <span class="inline-flex items-center gap-1 text-xs text-wr-ink-muted" id="del-reason-{{ $idSuffix }}-{{ $adventure->id }}">
        <i class="lock icon m-0" aria-hidden="true"></i> {{ $reason }}
    </span>
@endif
