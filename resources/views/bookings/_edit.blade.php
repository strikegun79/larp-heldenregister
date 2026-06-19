<span data-modal-title hidden>Anmeldung bearbeiten · {{ $booking->participant_name }}</span>

<p class="text-stone-500 mb-3">{{ $adventure->name }}</p>

<form id="booking-edit-form" data-stack-close method="POST" action="{{ route('adventures.bookings.update', [$adventure, $booking]) }}"
      class="ui form">
    @csrf @method('PUT')

    <div class="field">
        <label>Rolle</label>
        <select name="event_role_id" required>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected($booking->event_role_id == $role->id)>{{ $role->description }}</option>
            @endforeach
        </select>
    </div>

    <fieldset class="border border-stone-200 rounded p-3 mb-3">
        <legend class="text-sm font-medium text-stone-600 px-1">Optionale Angaben</legend>
        <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2"><input type="checkbox" name="fotoerlaubnis" value="1" @checked($booking->fotoerlaubnis)> Fotoerlaubnis</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="vegetarier" value="1" @checked($booking->vegetarier)> Vegetarier</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="leih_tunika" value="1" @checked($booking->leih_tunika)> Leih-Tunika</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="leih_waffe" value="1" @checked($booking->leih_waffe)> Leih-Waffe</label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="nsc" value="1" @checked($booking->nsc)> NSC
                <span class="text-stone-400 text-xs cursor-help"
                      data-tooltip="Non-Spieler-Charakter: Dein Kind übernimmt eine Statistenrolle statt als eigener Held zu spielen."
                      data-position="top center">(?)</span>
            </label>
        </div>
    </fieldset>

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

    <div class="field required">
        <label>Kontaktrufnummer (Notfallkontakt)</label>
        <input type="tel" name="kontakt_telefon" maxlength="100" required
               value="{{ old('kontakt_telefon', $booking->kontakt_telefon ?? $userPhone ?? '') }}"
               placeholder="z. B. +49 123 456789">
        @if (!$booking->kontakt_telefon && $userPhone)
            <small class="text-stone-400">Aus dem Spielerprofil übernommen – du kannst die Nummer ändern.</small>
        @endif
    </div>

    </form>

<div data-modal-actions hidden>
    <button type="submit" form="booking-edit-form" class="ui primary button">Speichern</button>
</div>
