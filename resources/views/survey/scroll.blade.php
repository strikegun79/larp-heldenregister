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
                    @if($paged)
                        Seite 1 von {{ count($groups) }}
                    @else
                        Frage 0 von {{ $questions->count() }}
                    @endif
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

                {{-- Issue #46: Fragen nach Bereichen; mit „Bereiche als Seiten“ ist jeder Bereich eine Seite --}}
                @php($questionNumber = 0)
                @foreach($groups as $p => $group)
                    <div class="survey-page" data-page="{{ $p + 1 }}" @if($paged && $p > 0) hidden @endif>
                        @if($group['section'])
                            <div class="survey-section-header">
                                <h2 class="survey-section-title" tabindex="-1">{{ $group['number'] }}. {{ $group['section']->question_text }}</h2>
                                @if($group['section']->subtitle())
                                    <p class="survey-section-subtitle">{{ $group['section']->subtitle() }}</p>
                                @endif
                            </div>
                        @endif

                        @foreach($group['questions'] as $question)
                            @php($questionNumber++)
                            @include('survey._scroll_question', ['question' => $question, 'number' => $questionNumber])
                        @endforeach

                        @if($paged)
                            <div class="survey-wizard-nav pb-6">
                                @if($p > 0)
                                    <button type="button" class="survey-btn-secondary" data-page-go="{{ $p }}">← Zurück</button>
                                @endif
                                @if($p < count($groups) - 1)
                                    <button type="button" class="survey-btn-primary" data-page-go="{{ $p + 2 }}">Weiter →</button>
                                @else
                                    <button type="submit" class="survey-btn-primary">Fragebogen abschicken</button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="pb-6">
                    @unless($paged)
                        <button type="submit" class="survey-btn-primary w-full">
                            Fragebogen abschicken
                        </button>
                    @endunless
                    <p class="text-xs text-center text-stone-400 mt-3">
                        Das Ausfüllen ist freiwillig. Nicht beantwortete optionale Fragen werden einfach übersprungen.
                    </p>
                </div>

            </form>
        </div>
    </div>

    <script>
    (function() {
        const fill  = document.getElementById('scroll-progress-fill');
        const label = document.getElementById('scroll-progress-label');
        const pages = document.querySelectorAll('.survey-page');
        const paged = {{ $paged ? 'true' : 'false' }};

        // Issue #46: Bereiche als Seiten – eine Seite zeigen, Fortschritt nach Seiten.
        function showPage(n, focus = true) {
            pages.forEach((el) => { el.hidden = Number(el.dataset.page) !== n; });
            const pct = Math.round((n / pages.length) * 100);
            if (fill)  fill.style.width = pct + '%';
            if (label) label.textContent = 'Seite ' + n + ' von ' + pages.length;
            if (!focus) return;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            pages[n - 1]?.querySelector('h2')?.focus({ preventScroll: true });
        }

        if (paged) {
            document.querySelectorAll('[data-page-go]').forEach((btn) => {
                btn.addEventListener('click', () => showPage(Number(btn.dataset.pageGo)));
            });
            showPage(1, false);
        } else {
            // Sticky-Fortschrittsbalken beim Scrollen
            const questions = document.querySelectorAll('[data-question-index]');
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
        }

        // Zum ersten Fehler-Feld (bei Seiten: dessen Seite öffnen)
        const firstError = document.querySelector('.survey-question-error');
        if (firstError) {
            const page = firstError.closest('.survey-page');
            if (paged && page) showPage(Number(page.dataset.page), false);
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.closest('fieldset')?.querySelector('input,textarea')?.focus();
        }
    })();
    </script>
</x-public-layout>
