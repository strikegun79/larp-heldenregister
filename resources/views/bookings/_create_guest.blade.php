<span data-modal-title hidden>Gast anmelden · {{ $adventure->name }}</span>

<div class="ui warning message" style="display:block">
    <strong>Hinweis:</strong> Für Gäste werden <em>keine Erfahrungspunkte</em> gesammelt.
</div>

@if (! $adventure->registrationOpen())
    <p class="text-stone-500">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@else
    @if ($adventure->isFull())
        <p class="mb-3 text-orange-600">Das Abenteuer ist voll – Gäste kommen auf die Warteliste.</p>
    @endif

    <form id="booking-guest-form" data-stack-close method="POST" action="{{ route('adventures.bookings.store-guest', $adventure) }}" class="ui form">
        @csrf
        <div class="two fields">
            <div class="field">
                <label>Vorname</label>
                <input type="text" name="guest_name" required>
            </div>
            <div class="field">
                <label>Nachname</label>
                <input type="text" name="guest_lastname" required>
            </div>
        </div>
        <div class="two fields">
            <div class="field">
                <label>Alter</label>
                <input type="number" name="guest_age" min="0" max="120">
            </div>
            <div class="field">
                <label>Ort</label>
                <input type="text" name="guest_place">
            </div>
        </div>
        <div class="field">
            <label>Rolle</label>
            <select name="event_role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->description }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-2 my-2">
            <label class="flex items-center gap-2"><input type="checkbox" name="fotoerlaubnis" value="1"> Fotoerlaubnis</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="vegetarier" value="1"> Vegetarier</label>
        </div>

        <div class="field">
            <label>Allergien</label>
            <textarea name="allergien" rows="2"></textarea>
        </div>
        <div class="field">
            <label>Erreichbarkeit</label>
            <textarea name="erreichbarkeit" rows="2"></textarea>
        </div>

        <label class="flex items-center gap-2 my-2"><input type="checkbox" name="agb" value="1" required> Ich akzeptiere die AGB</label>
    </form>

    <div data-modal-actions hidden>
        <button type="submit" form="booking-guest-form" class="ui primary button">Gast anmelden</button>
    </div>
@endif
