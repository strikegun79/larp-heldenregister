<span data-modal-title hidden>Spieler bearbeiten: {{ $player->full_name }}</span>

<form method="POST" action="{{ route('players.update', $player) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('players._form')
</form>
