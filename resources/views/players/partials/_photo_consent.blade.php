{{-- Spieler-Detail – Fotoerlaubnis je Anmeldung (N-2 / Art. 7 DSGVO, Waldritter v1).
     Status als Text mit Symbol, Widerruf/Erteilung mit Rückfrage. Erwartet: $player. --}}
<div class="space-y-3">
    <div class="wr-notice" role="note">
        <i class="camera icon m-0 mt-0.5" aria-hidden="true"></i>
        <p>
            Die Fotoerlaubnis kann jederzeit widerrufen werden (Art. 7 DSGVO). Der Widerruf gilt ab sofort für
            zukünftige Aufnahmen; bereits veröffentlichte Fotos werden auf Anfrage entfernt.
        </p>
    </div>

    <ul class="wr-rows" role="list">
        @foreach ($player->bookings as $bk)
            <li class="wr-row">
                <div class="min-w-0">
                    <span class="font-semibold break-words">{{ $bk->adventure?->name ?? '—' }}</span>
                    <span class="block text-sm text-wr-ink-muted">{{ optional($bk->adventure?->start_at)->format('d.m.Y') ?? '—' }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($bk->fotoerlaubnis)
                        <span class="wr-pill wr-pill--success"><i class="check circle icon" aria-hidden="true"></i>Fotoerlaubnis erteilt</span>
                    @else
                        <span class="wr-pill wr-pill--waitlist"><i class="ban icon" aria-hidden="true"></i>keine Fotoerlaubnis</span>
                    @endif
                    <form method="POST"
                          action="{{ route('adventures.bookings.fotoerlaubnis', [$bk->adventure_id, $bk]) }}"
                          data-refresh-modal
                          data-confirm="{{ $bk->fotoerlaubnis ? 'Fotoerlaubnis für diese Veranstaltung wirklich widerrufen?' : 'Fotoerlaubnis für diese Veranstaltung erneut erteilen?' }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="wr-btn wr-btn--small {{ $bk->fotoerlaubnis ? 'wr-btn--danger' : 'wr-btn--secondary' }}">
                            {{ $bk->fotoerlaubnis ? 'Widerrufen' : 'Erneut erteilen' }}
                        </button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
</div>
