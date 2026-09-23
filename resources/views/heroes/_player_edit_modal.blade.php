<span data-modal-title hidden>Charakter bearbeiten</span>

<form method="POST" action="{{ route('heroes.player-info', $hero) }}"
      data-stack-close
      class="ui form">
    @csrf
    @method('PATCH')

    <div class="field">
        <label for="pem-character-name">Charaktername</label>
        <input type="text" id="pem-character-name" name="character_name"
               value="{{ old('character_name', $hero->character_name) }}"
               maxlength="150" placeholder="Name deines Helden">
    </div>

    <div class="field">
        <label for="pem-homeplace">Heimatort</label>
        <input type="text" id="pem-homeplace" name="homeplace"
               value="{{ old('homeplace', $hero->homeplace) }}"
               maxlength="150" placeholder="Woher kommt dein Held?">
    </div>

    <div class="field">
        <label for="pem-description">Steckbrief / Hintergrund</label>
        <textarea id="pem-description" name="description"
                  rows="7" maxlength="5000"
                  placeholder="Hintergrundgeschichte, Aussehen, Besonderheiten …">{{ old('description', $hero->description) }}</textarea>
    </div>

    <div data-modal-actions hidden>
        <button type="submit" class="ui primary button">
            <i class="save icon"></i> Speichern
        </button>
    </div>
</form>
