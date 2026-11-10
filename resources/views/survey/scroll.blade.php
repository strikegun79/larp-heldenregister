<x-public-layout :title="'Fragebogen: ' . $link->survey->adventure->name">
    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="survey-wrap">
        <div class="survey-form-wrap--adult">

            {{-- Kopfzeile --}}
            <div class="survey-card mb-4 text-center py-3">
                <h1 class="text-xl font-semibold text-waldritter" style="font-family:'EB Garamond',serif;">
                    {{ $link->survey->title }}
                </h1>
                <p class="text-sm text-stone-500 mt-1">{{ $link->survey->adventure->name }}</p>
            </div>

            {{-- Sticky Fortschrittsleiste --}}
            <div id="scroll-progress-container"
                 class="sticky top-0 z-20 bg-[#f5edd8]/90 backdrop-blur py-2 px-3 border-b border-[#8b6542]/30 mb-4">
                <div class="survey-rücklauf-bar">
                    <div class="survey-rücklauf-fill" id="scroll-progress-fill" style="width:0%"></div>
                </div>
                <p class="text-xs text-center text-stone-500 mt-1" id="scroll-progress-label">
                    Frage 0 von {{ $questions->count() }}
                </p>
            </div>

            <form method="POST"
                  action="{{ route('survey.submit', ['token' => $link->token]) }}"
                  id="survey-form"
                  novalidate>
                @csrf

                {{-- Validierungsfehler --}}
                @if($errors->any())
                    <div class="survey-card mb-4 p-4" style="border-color:#b91c1c;background:#fff5f5;">
                        <p class="font-semibold text-red-700 mb-2">Bitte prüfe deine Antworten:</p>
                        <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @foreach($questions as $index => $question)
                    <div class="survey-card mb-4" data-question-index="{{ $index + 1 }}">
                        <fieldset>
                            <legend class="survey-question-label">
                                <span class="text-stone-400 text-sm mr-1">{{ $index + 1 }}.</span>
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
                            @endif

                            @error("answers.{$question->id}")
                                <p class="survey-question-error" role="alert">{{ $message }}</p>
                            @enderror

                        </fieldset>
                    </div>
                @endforeach

                <div class="pb-6">
                    <button type="submit" class="survey-btn-primary w-full">
                        Fragebogen abschicken
                    </button>
                    <p class="text-xs text-center text-stone-400 mt-3">
                        Das Ausfüllen ist freiwillig. Nicht beantwortete optionale Fragen werden einfach übersprungen.
                    </p>
                </div>

            </form>
        </div>
    </div>

    <script>
    // Sticky-Fortschrittsbalken beim Scrollen
    (function() {
        const questions = document.querySelectorAll('[data-question-index]');
        const fill  = document.getElementById('scroll-progress-fill');
        const label = document.getElementById('scroll-progress-label');
        const total = questions.length;

        function update() {
            let answered = 0;
            questions.forEach((el, i) => {
                const rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight * 0.6) answered = i + 1;
            });
            const pct = total ? Math.round((answered / total) * 100) : 0;
            if (fill)  fill.style.width  = pct + '%';
            if (label) label.textContent = 'Frage ' + answered + ' von ' + total;
        }

        window.addEventListener('scroll', update, { passive: true });
        update();
    })();

    // Zum ersten Fehler-Feld scrollen
    const firstError = document.querySelector('.survey-question-error');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstError.closest('fieldset')?.querySelector('input,textarea')?.focus();
    }
    </script>
</x-public-layout>
