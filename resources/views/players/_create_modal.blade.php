<span data-modal-title hidden>Neuer Spieler</span>

{{-- Waldritter v1: eigener Wrapper, weil das Formular im globalen Modal lädt --}}
<div class="wr-v1">
    <form id="player-create-form" method="POST" action="{{ route('players.store') }}" enctype="multipart/form-data">
        @include('players._form')
    </form>
</div>

<div data-modal-actions hidden>
    <button type="submit" form="player-create-form" class="wr-btn wr-btn--primary">Speichern</button>
</div>
