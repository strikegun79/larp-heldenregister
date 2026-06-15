<span data-modal-title hidden>Neuer Spieler</span>

<form id="player-create-form" method="POST" action="{{ route('players.store') }}" enctype="multipart/form-data">
    @include('players._form')
</form>

<div data-modal-actions hidden>
    <button type="submit" form="player-create-form" class="ui primary button">Speichern</button>
</div>
