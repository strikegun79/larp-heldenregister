<span data-modal-title hidden>Spieler bearbeiten: {{ $player->full_name }}</span>

{{-- Waldritter v1: eigener Wrapper, weil das Formular im globalen Modal lädt --}}
<div class="wr-v1">
    <form id="player-edit-form" method="POST" action="{{ route('players.update', $player) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('players._form')
    </form>
</div>

<div data-modal-actions hidden>
    <button type="submit" form="player-edit-form" class="wr-btn wr-btn--primary">Speichern</button>
</div>
