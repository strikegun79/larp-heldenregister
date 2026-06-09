<span data-modal-title hidden>Spieler bearbeiten: {{ $player->full_name }}</span>

<form method="POST" action="{{ route('players.update', $player) }}">
    @method('PUT')
    @include('players._form')
</form>
