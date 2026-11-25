{{-- Eine Frage im Scroll-Formular (Erwachsene). Erwartet: $question, $number --}}
<div class="survey-card mb-4" data-question-index="{{ $number }}">
    <fieldset>
        <legend class="survey-question-label">
            <span class="text-stone-400 text-sm mr-1">{{ $number }}.</span>
            {{ $question->question_text }}
            @unless($question->required)
                <span class="optional-badge">(freiwillig)</span>
            @endunless
        </legend>

        @if($question->type === 'rating')
            {{-- 1–10 Zahlen-Buttons für Erwachsene --}}
            <div class="rating-buttons" role="radiogroup">
                @for($v = 1; $v <= 10; $v++)
                    <input type="radio"
                           name="answers[{{ $question->id }}]"
                           id="q{{ $question->id }}_r{{ $v }}"
                           value="{{ $v }}"
                           {{ old("answers.{$question->id}") == $v ? 'checked' : '' }}>
                    <label for="q{{ $question->id }}_r{{ $v }}">{{ $v }}</label>
                @endfor
            </div>
            <p class="text-xs text-stone-400 mt-1">1 = nicht gut, 10 = sehr gut</p>

        @elseif($question->type === 'text')
            <textarea name="answers[{{ $question->id }}]"
                      id="q{{ $question->id }}"
                      class="parchment-textarea"
                      placeholder="Deine Anmerkungen …"
                      maxlength="2000">{{ old("answers.{$question->id}") }}</textarea>

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
</div>
