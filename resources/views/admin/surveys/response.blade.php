<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                    Antworten: {{ $link->name }}
                </h2>
                <p class="text-sm text-stone-500 mt-0.5">
                    {{ $survey->title }}
                    &nbsp;·&nbsp;
                    {{ $survey->adventure->name }}
                    &nbsp;·&nbsp;
                    Eingegangen: {{ $link->completed_at?->format('d.m.Y H:i') ?? '–' }}
                </p>
            </div>
            <a href="{{ route('admin.surveys.results', $survey) }}" class="ui button">
                ← Zurück zur Übersicht
            </a>
        </div>
    </x-slot>

    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(! $link->response)
                <div class="ui info message">
                    Für diesen Link liegt noch keine Antwort vor.
                </div>
            @else
                @foreach($survey->template->questions as $question)
                    @php
                        $answer = $link->response->answers->firstWhere('survey_template_question_id', $question->id);
                    @endphp
                    <div class="survey-result-card mb-4">
                        <div class="survey-result-card-header">
                            @if($question->type === 'rating') 📊
                            @elseif($question->type === 'text') 📝
                            @else ✅ @endif
                            {{ $question->question_text }}
                        </div>
                        <div class="survey-result-card-body">
                            @if(! $answer)
                                <p class="text-stone-400 italic text-sm">Keine Antwort</p>
                            @elseif($question->type === 'rating')
                                <div class="flex items-center gap-3">
                                    <span class="survey-avg-value text-3xl">{{ $answer->rating_answer }}</span>
                                    <span class="text-stone-400 text-sm">von 10</span>
                                </div>
                            @elseif($question->type === 'text')
                                @can('survey.admin')
                                    @if(filled($answer->text_answer))
                                        <blockquote class="survey-text-quote">{{ $answer->text_answer }}</blockquote>
                                    @else
                                        <p class="text-stone-400 italic text-sm">Keine Antwort</p>
                                    @endif
                                @else
                                    <p class="text-stone-400 italic text-sm">
                                        Freitext nur für Umfrage-Admins sichtbar.
                                    </p>
                                @endcan
                            @elseif($question->type === 'yes_no')
                                @if($answer->yes_no_answer === null)
                                    <p class="text-stone-400 italic text-sm">Keine Antwort</p>
                                @elseif($answer->yes_no_answer)
                                    <span class="ui green label text-base px-4 py-2">✅ Ja</span>
                                @else
                                    <span class="ui red label text-base px-4 py-2">❌ Nein</span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
