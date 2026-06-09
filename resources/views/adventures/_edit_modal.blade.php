<span data-modal-title hidden>Abenteuer bearbeiten: {{ $adventure->name }}</span>

<form method="POST" action="{{ route('adventures.update', $adventure) }}">
    @method('PUT')
    @include('adventures._form')
</form>
