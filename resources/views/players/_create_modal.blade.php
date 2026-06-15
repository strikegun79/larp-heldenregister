<span data-modal-title hidden>Neuer Spieler</span>

<form method="POST" action="{{ route('players.store') }}" enctype="multipart/form-data">
    @include('players._form')
</form>
