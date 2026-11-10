<x-public-layout :title="'Fragebogen: ' . $link->survey->adventure->name">
    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="survey-wrap">
        <div class="survey-form-wrap--child">

            {{-- Wizard-Container, gesteuert via JS --}}
            <div id="survey-wizard" data-total="{{ $questions->count() }}">

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
                        <div class="survey-step survey-card mb-0"
                             data-step="{{ $index + 1 }}"
                             style="{{ $index > 0 ? 'display:none;' : '' }}">

                            {{-- Fortschrittsbalken --}}
                            <div class="survey-progress-label">
                                Frage {{ $index + 1 }} von {{ $questions->count() }}
                            </div>
                            <div class="survey-progress-bar mb-4">
                                <div class="survey-progress-bar-fill"
                                     style="width: {{ round((($index + 1) / $questions->count()) * 100) }}%">
                                </div>
                            </div>

                            {{-- Frage --}}
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
                                @endif

                                @error("answers.{$question->id}")
                                    <p class="survey-question-error" role="alert">{{ $message }}</p>
                                @enderror
                            </fieldset>

                            {{-- Navigation --}}
                            <div class="survey-wizard-nav mt-6">
                                @if($index > 0)
                                    <button type="button"
                                            class="survey-btn-secondary"
                                            onclick="wizardGo({{ $index }})">
                                        ← Zurück
                                    </button>
                                @endif

                                @if($index < $questions->count() - 1)
                                    <button type="button"
                                            class="survey-btn-primary"
                                            onclick="wizardGo({{ $index + 2 }})">
                                        Weiter →
                                    </button>
                                @else
                                    <button type="submit" class="survey-btn-primary">
                                        🛡 Abschicken!
                                    </button>
                                @endif
                            </div>

                        </div>
                    @endforeach

                </form>

            </div>{{-- #survey-wizard --}}

        </div>
    </div>

    <script>
    function wizardGo(step) {
        document.querySelectorAll('.survey-step').forEach(el => {
            el.style.display = 'none';
        });
        const target = document.querySelector('[data-step="' + step + '"]');
        if (target) {
            target.style.display = '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // Schildskala: Hover-Aufleuchten der Vorgänger-Schilde
    document.querySelectorAll('.shield-rating').forEach(group => {
        const labels = group.querySelectorAll('label');
        labels.forEach((label, i) => {
            label.addEventListener('mouseenter', () => {
                labels.forEach((l, j) => {
                    l.classList.toggle('lit', j <= i);
                });
            });
            label.addEventListener('mouseleave', () => {
                labels.forEach(l => l.classList.remove('lit'));
            });
        });
    });
    </script>
</x-public-layout>
