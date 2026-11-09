<span data-modal-title hidden>Spieler bearbeiten: {{ $player->full_name }}</span>

<form id="player-edit-form" method="POST" action="{{ route('players.update', $player) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('players._form')
</form>

<div data-modal-actions hidden>
    <button type="submit" form="player-edit-form" class="ui primary button">Speichern</button>
</div>
