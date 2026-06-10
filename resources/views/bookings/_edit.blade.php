<span data-modal-title hidden>Anmeldung bearbeiten · {{ $booking->player?->full_name }}</span>

<p class="text-stone-500 mb-3">{{ $adventure->name }}</p>

<form method="POST" action="{{ route('adventures.bookings.update', [$adventure, $booking]) }}"
      class="ui form" data-refresh-modal>
    @csrf @method('PUT')

    <div class="field">
        <label>Rolle</label>
        <select name="event_role_id" required>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected($booking->event_role_id == $role->id)>{{ $role->description }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-2 my-2">
        @foreach (['fotoerlaubnis' => 'Fotoerlaubnis', 'vegetarier' => 'Vegetarier', 'leih_tunika' => 'Leih-Tunika', 'leih_waffe' => 'Leih-Waffe', 'nsc' => 'NSC'] as $field => $label)
            <label class="flex items-center gap-2"><input type="checkbox" name="{{ $field }}" value="1" @checked($booking->$field)> {{ $label }}</label>
        @endforeach
    </div>

    <div class="field">
        <label>Allergien</label>
        <textarea name="allergien" rows="2">{{ $booking->allergien }}</textarea>
    </div>

    <div class="field">
        <label>Medikamente</label>
        <textarea name="medikamente" rows="2">{{ $booking->medikamente }}</textarea>
    </div>

    <div class="field">
        <label>Erreichbarkeit</label>
        <textarea name="erreichbarkeit" rows="2">{{ $booking->erreichbarkeit }}</textarea>
    </div>

    <div class="flex items-center gap-2 mt-3">
        <button type="submit" class="ui primary button">Speichern</button>
        <a href="{{ route('adventures.show', $adventure) }}" data-modal-subview="{{ route('adventures.show', $adventure) }}"
           class="ui basic button">Zurück</a>
    </div>
</form>
