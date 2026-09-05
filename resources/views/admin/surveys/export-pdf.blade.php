<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $survey->title }} – Umfrage-Export</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #2c1a0e;
            background: #fff;
        }

        /* Jede Antwort beginnt auf einer neuen Seite */
        .response-page {
            page-break-after: always;
            padding: 1.5cm 2cm 1.2cm 2cm;
            min-height: 100vh;
            display: block;
        }

        .response-page:last-child {
            page-break-after: avoid;
        }

        /* Kopfzeile */
        .page-header {
            border-bottom: 2px solid #8b6542;
            padding-bottom: 7px;
            margin-bottom: 14px;
        }

        .page-header h1 {
            font-size: 11pt;
            font-weight: bold;
            color: #5a3a22;
        }

        .page-header .meta {
            font-size: 7pt;
            color: #6b5244;
            margin-top: 3px;
        }

        .page-header .person {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 6px;
            color: #2c1a0e;
        }

        /* Status-Badges */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }

        .badge-green  { background: #d4edda; color: #155724; }
        .badge-grey   { background: #e9ecef; color: #495057; }
        .badge-yellow { background: #fff3cd; color: #856404; }

        /* Frage-Block */
        .question-block {
            margin-bottom: 8px;
            padding: 7px 10px;
            background: #fdf8f0;
            border-left: 3px solid #c8962a;
            border-radius: 0 4px 4px 0;
        }

        .question-label {
            font-size: 8pt;
            color: #6b5244;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .question-type-icon {
            font-size: 7pt;
            color: #9b7a5a;
            margin-right: 3px;
        }

        .answer-rating {
            font-size: 9pt;
            font-weight: bold;
            color: #c8962a;
        }

        .answer-rating span {
            font-size: 9pt;
            color: #9b7a5a;
            font-weight: normal;
        }

        .answer-text {
            font-size: 9pt;
            font-style: italic;
            color: #3d2b1f;
            line-height: 1.5;
            padding: 4px 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .answer-yes  { font-size: 9pt; color: #155724; font-weight: bold; }
        .answer-no   { font-size: 9pt; color: #721c24; font-weight: bold; }
        .answer-none { font-size: 9pt; color: #9b9b9b; font-style: italic; }

        /* Fußzeile */
        .page-footer {
            position: fixed;
            bottom: 0.6cm;
            left: 2cm;
            right: 2cm;
            font-size: 6pt;
            color: #9b9b9b;
            border-top: 1px solid #e0d4c0;
            padding-top: 3px;
            display: flex;
            justify-content: space-between;
        }

        /* Deckblatt */
        .cover-page {
            page-break-after: always;
            padding: 2.5cm 2cm;
            text-align: center;
        }

        .cover-title {
            font-size: 16pt;
            font-weight: bold;
            color: #5a3a22;
            margin-bottom: 10px;
        }

        .cover-subtitle {
            font-size: 11pt;
            color: #8b6542;
            margin-bottom: 24px;
        }

        .cover-stats {
            display: inline-block;
            text-align: left;
            background: #fdf8f0;
            border: 1px solid #c8962a;
            border-radius: 6px;
            padding: 16px 24px;
            margin: 16px auto;
        }

        .cover-stats table { border-collapse: collapse; }
        .cover-stats td { padding: 3px 10px 3px 0; font-size: 9pt; }
        .cover-stats td:first-child { color: #8b6542; }
        .cover-stats td:last-child { font-weight: bold; }
    </style>
</head>
<body>

    {{-- Deckblatt --}}
    <div class="cover-page">
        <div class="cover-title">{{ $survey->title }}</div>
        <div class="cover-subtitle">Umfrage-Ergebnisse</div>

        <div class="cover-stats">
            <table>
                <tr>
                    <td>Veranstaltung:</td>
                    <td>{{ $survey->adventure->name }}</td>
                </tr>
                <tr>
                    <td>Vorlage:</td>
                    <td>{{ $survey->template->name }}</td>
                </tr>
                <tr>
                    <td>Zielgruppe:</td>
                    <td>{{ \App\Models\SurveyTemplate::TARGET_GROUPS[$survey->template->target_group] ?? $survey->template->target_group }}</td>
                </tr>
                <tr>
                    <td>Eingegangene Antworten:</td>
                    <td>{{ $links->count() }}</td>
                </tr>
                @if($survey->sent_at)
                <tr>
                    <td>Versendet am:</td>
                    <td>{{ $survey->sent_at->format('d.m.Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td>Exportiert am:</td>
                    <td>{{ now()->format('d.m.Y H:i') }}</td>
                </tr>
            </table>
        </div>

        <p style="font-size:8pt;color:#9b9b9b;margin-top:30px;">
            Dieser Export ist anonymisiert. Rückschlüsse auf einzelne Personen sind nicht möglich.<br>
            Freitexte werden nach 2 Jahren automatisch gelöscht · Aggregierte Auswertungen: dauerhaft.
        </p>
    </div>

    {{-- Eine Seite pro Antwort --}}
    @foreach($links as $link)
        <div class="response-page">

            <div class="page-header">
                <h1>{{ $survey->title }}</h1>
                <div class="meta">
                    {{ $survey->adventure->name }}
                    &nbsp;·&nbsp;
                    Exportiert {{ now()->format('d.m.Y') }}
                    &nbsp;·&nbsp;
                    Antwort {{ $loop->iteration }} von {{ $links->count() }}
                </div>
                <div class="person">
                    Teilnehmer {{ $loop->iteration }}
                </div>
                <div style="margin-top:4px;font-size:7pt;color:#6b5244;">
                    Zielgruppe: {{ \App\Models\SurveyTemplate::TARGET_GROUPS[$link->target_type] ?? $link->target_type }}
                    &nbsp;·&nbsp;
                    Eingegangen: {{ $link->completed_at?->format('d.m.Y') ?? '–' }}
                </div>
            </div>

            @if(! $link->response)
                <p class="answer-none">Keine Antwort eingegangen.</p>
            @else
                @foreach($survey->template->questions as $question)
                    @php
                        $answer = $link->response->answers->firstWhere('survey_template_question_id', $question->id);
                    @endphp
                    <div class="question-block">
                        <div class="question-label">
                            @if($question->type === 'rating') [Bewertung]
                            @elseif($question->type === 'text') [Freitext]
                            @else [Ja/Nein]
                            @endif
                            {{ $question->question_text }}
                        </div>

                        @if(! $answer)
                            <span class="answer-none">Keine Antwort</span>
                        @elseif($question->type === 'rating')
                            <div class="answer-rating">
                                {{ $answer->rating_answer }}
                                <span>/ 10</span>
                            </div>
                        @elseif($question->type === 'text')
                            @if(filled($answer->text_answer))
                                <div class="answer-text">{{ $answer->text_answer }}</div>
                            @else
                                <span class="answer-none">Keine Antwort</span>
                            @endif
                        @elseif($question->type === 'yes_no')
                            @if($answer->yes_no_answer === null)
                                <span class="answer-none">Keine Antwort</span>
                            @elseif($answer->yes_no_answer)
                                <span class="answer-yes">✓ Ja</span>
                            @else
                                <span class="answer-no">✗ Nein</span>
                            @endif
                        @endif
                    </div>
                @endforeach
            @endif

        </div>
    @endforeach

    <div class="page-footer">
        <span>{{ $survey->title }} · {{ $survey->adventure->name }}</span>
        <span>Anonymisierter Export · {{ now()->format('d.m.Y') }}</span>
    </div>

</body>
</html>
