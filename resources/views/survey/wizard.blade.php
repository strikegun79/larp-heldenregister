<x-public-layout :title="'Fragebogen: ' . $link->survey->adventure->name">
    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="survey-wrap">
        <div class="survey-form-wrap--child">

            @isset($preview)
                @include('survey._preview_banner')
            @endisset

            {{-- Wizard-Container, gesteuert via JS --}}
            <div id="survey-wizard">

                <form method="POST"
                      action="{{ $preview['action'] ?? route('survey.submit', ['token' => $link->token]) }}"
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

                    {{-- Issue #46: Schritte – mit „Bereiche als Seiten“ ein Bereich je Schritt, sonst eine Frage je Schritt
                         (Bereichstitel steht dann über jeder Frage des Bereichs). --}}
                    @php
                        $steps = [];
                        foreach ($groups as $group) {
                            if ($paged) {
                                $steps[] = $group;
                                continue;
                            }
                            foreach ($group['questions'] as $groupQuestion) {
                                $steps[] = ['section' => $group['section'], 'number' => $group['number'], 'questions' => [$groupQuestion]];
                            }
                        }
                        $stepCount = count($steps);
                    @endphp
                    @foreach($steps as $index => $step)
                        <div class="survey-step survey-card mb-0"
                             data-step="{{ $index + 1 }}"
                             style="{{ $index > 0 ? 'display:none;' : '' }}">

                            {{-- Fortschrittsbalken --}}
                            <div class="survey-progress-label">
                                {{ $paged ? 'Seite' : 'Frage' }} {{ $index + 1 }} von {{ $stepCount }}
                            </div>
                            <div class="survey-progress-bar mb-4">
                                <div class="survey-progress-bar-fill"
                                     style="width: {{ round((($index + 1) / $stepCount) * 100) }}%">
                                </div>
                            </div>

                            @if($step['section'])
                                <div class="survey-section-header">
                                    <h2 class="survey-section-title">{{ $step['number'] }}. {{ $step['section']->question_text }}</h2>
                                    @if($step['section']->subtitle())
                                        <p class="survey-section-subtitle">{{ $step['section']->subtitle() }}</p>
                                    @endif
                                </div>
                            @endif

                            @foreach($step['questions'] as $question)
                                <div class="survey-step-question">
                                    @include('survey._wizard_question', ['question' => $question])
                                </div>
                            @endforeach

                            {{-- Navigation --}}
                            <div class="survey-wizard-nav mt-6">
                                @if($index > 0)
                                    <button type="button"
                                            class="survey-btn-secondary"
                                            onclick="wizardGo({{ $index }})">
                                        ← Zurück
                                    </button>
                                @endif

                                @if($index < $stepCount - 1)
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

    // Issue #46: Bei Validierungsfehlern den Schritt mit dem ersten Fehler öffnen.
    (function () {
        const firstError = document.querySelector('.survey-step .survey-question-error');
        const step = firstError?.closest('.survey-step');
        if (step) {
            wizardGo(Number(step.dataset.step));
        }
    })();

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
