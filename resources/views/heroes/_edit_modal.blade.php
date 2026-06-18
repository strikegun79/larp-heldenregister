<span data-modal-title hidden>Held bearbeiten: {{ $hero->character_name }}</span>

<form method="POST" action="{{ route('heroes.update', $hero) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('heroes._form')
</form>
