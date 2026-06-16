<span data-modal-title hidden>Abenteuer bearbeiten: {{ $adventure->name }}</span>

<form id="adventure-edit-form" method="POST" action="{{ route('adventures.update', $adventure) }}">
    @method('PUT')
    @include('adventures._form', ['inModal' => true])
</form>

<div data-modal-actions hidden>
    <button type="submit" form="adventure-edit-form" class="ui primary button">
        <i class="save icon"></i> Speichern
    </button>
</div>
