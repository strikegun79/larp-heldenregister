{{-- Eine Frage im Wizard (Kinder/Jugendliche). Erwartet: $question --}}
<fieldset>
    <legend class="survey-question-label">
        {{ $question->question_text }}
        @unless($question->required)
            <span class="optional-badge">(freiwillig)</span>
        @endunless
    </legend>

    @if($question->type === 'rating')
        {{-- Schildskala 1–5 für Kinder --}}
        <div class="shield-rating" role="radiogroup">
            @php
                $labels = ['', 'naja', 'okay', 'gut', 'toll', 'super!'];
            @endphp
            @for($v = 1; $v <= 5; $v++)
                <div>
                    <input type="radio"
                           name="answers[{{ $question->id }}]"
                           id="q{{ $question->id }}_r{{ $v }}"
                           value="{{ $v * 2 }}"
                           {{ old("answers.{$question->id}") == $v * 2 ? 'checked' : '' }}>
                    <label for="q{{ $question->id }}_r{{ $v }}">
                        <span class="shield-icon">🛡</span>
                        <span class="shield-label">{{ $labels[$v] }}</span>
                    </label>
                </div>
            @endfor
        </div>

    @elseif($question->type === 'text')
        <textarea name="answers[{{ $question->id }}]"
                  id="q{{ $question->id }}"
                  class="parchment-textarea"
                  placeholder="Schreib hier auf, was dir in den Sinn kommt …"
                  maxlength="2000"
                  aria-describedby="q{{ $question->id }}_hint">{{ old("answers.{$question->id}") }}</textarea>
        <p id="q{{ $question->id }}_hint" class="text-xs text-stone-400 mt-1">
            Freiwillig – du kannst diese Frage auch überspringen.
        </p>

    @elseif($question->type === 'yes_no')
        <div class="yes-no-buttons" role="radiogroup">
            <input type="radio"
                   name="answers[{{ $question->id }}]"
                   id="q{{ $question->id }}_yes"
                   value="1"
                   {{ old("answers.{$question->id}") === '1' ? 'checked' : '' }}>
            <label for="q{{ $question->id }}_yes" class="yes-label">
                ✅ Ja
            </label>

            <input type="radio"
                   name="answers[{{ $question->id }}]"
                   id="q{{ $question->id }}_no"
                   value="0"
                   {{ old("answers.{$question->id}") === '0' ? 'checked' : '' }}>
            <label for="q{{ $question->id }}_no" class="no-label">
                ❌ Nein
            </label>
        </div>

    @else
        {{-- Issue #24: Optionen mit Freitext / Smileys --}}
        @include('survey._question_extra')
    @endif

    @error("answers.{$question->id}")
        <p class="survey-question-error" role="alert">{{ $message }}</p>
    @enderror
</fieldset>
