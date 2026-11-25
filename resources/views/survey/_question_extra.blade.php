{{-- Issue #24: Eingabe der Fragetypen „Optionen mit Freitext“ und „Smileys (1–5)“ (Wizard und Scroll) --}}
@if($question->type === 'choice')
    @php
        $multiple = $question->allowsMultiple();
        $oldChoices = (array) old("answers.{$question->id}.choices", []);
    @endphp
    <div class="choice-buttons" role="{{ $multiple ? 'group' : 'radiogroup' }}">
        @foreach($question->choices() as $c => $choice)
            <input type="{{ $multiple ? 'checkbox' : 'radio' }}"
                   name="answers[{{ $question->id }}][choices][]"
                   id="q{{ $question->id }}_c{{ $c }}"
                   value="{{ $choice }}"
                   {{ in_array($choice, $oldChoices, true) ? 'checked' : '' }}>
            <label for="q{{ $question->id }}_c{{ $c }}">
                <span class="choice-mark" aria-hidden="true"></span>
                {{ $choice }}
            </label>
        @endforeach
    </div>
    @if($multiple)
        <p class="text-xs text-stone-400 mt-1">Du kannst mehrere Antworten auswählen.</p>
    @endif

    @if($question->hasFreeText())
        <label for="q{{ $question->id }}_text" class="block text-sm text-stone-600 mt-3 mb-1">
            Deine Nachricht <span class="optional-badge">(freiwillig)</span>
        </label>
        <textarea name="answers[{{ $question->id }}][text]"
                  id="q{{ $question->id }}_text"
                  class="parchment-textarea"
                  placeholder="Möchtest du noch etwas dazu sagen?"
                  maxlength="2000">{{ old("answers.{$question->id}.text") }}</textarea>
    @endif

    @foreach(["answers.{$question->id}.choices", "answers.{$question->id}.choices.*", "answers.{$question->id}.text"] as $errorKey)
        @error($errorKey)
            <p class="survey-question-error" role="alert">{{ $message }}</p>
        @enderror
    @endforeach

@elseif($question->type === 'smiley')
    <div role="radiogroup">
    <div class="smiley-rating">
        @foreach($question->smileyLabels() as $points => $label)
            <input type="radio"
                   name="answers[{{ $question->id }}]"
                   id="q{{ $question->id }}_s{{ $points }}"
                   value="{{ $points }}"
                   {{ (string) old("answers.{$question->id}") === (string) $points ? 'checked' : '' }}>
            <label for="q{{ $question->id }}_s{{ $points }}" title="{{ $points }} {{ $points === 1 ? 'Punkt' : 'Punkte' }}">
                <span class="smiley-icon" aria-hidden="true">{{ \App\Models\SurveyTemplateQuestion::SMILEYS[$points] }}</span>
                <span class="smiley-label">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    {{-- Issue #46: Zusatzantwort ohne Smiley (wird als 0 gespeichert, zählt nicht mit) --}}
    @if($question->hasNaOption())
        <div class="smiley-na">
            <input type="radio"
                   name="answers[{{ $question->id }}]"
                   id="q{{ $question->id }}_s0"
                   value="0"
                   {{ (string) old("answers.{$question->id}") === '0' ? 'checked' : '' }}>
            <label for="q{{ $question->id }}_s0">{{ $question->naLabel() }}</label>
        </div>
    @endif
    </div>
@endif
