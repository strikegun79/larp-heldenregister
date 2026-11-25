<?php

namespace App\Http\Controllers;

use App\Models\SurveyAnswer;
use App\Models\SurveyLink;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveyController extends Controller
{
    /** Begrüßungsscreen mit Kontext + Freiwilligkeitshinweis. */
    public function start(string $token): View|RedirectResponse
    {
        $link = SurveyLink::where('token', $token)
            ->with(['survey.template.questions', 'survey.adventure'])
            ->firstOrFail();

        if ($link->isCompleted()) {
            return view('survey.expired', ['reason' => 'completed', 'link' => $link]);
        }

        if ($link->isExpired()) {
            return view('survey.expired', ['reason' => 'expired', 'link' => $link]);
        }

        return view('survey.start', compact('link'));
    }

    /** Formular anzeigen – Wizard (Kinder) oder Scroll (Erwachsene). */
    public function show(string $token): View|RedirectResponse
    {
        $link = SurveyLink::where('token', $token)
            ->with(['survey.template.questions', 'survey.adventure'])
            ->firstOrFail();

        if ($link->isCompleted()) {
            return view('survey.expired', ['reason' => 'completed', 'link' => $link]);
        }

        if ($link->isExpired()) {
            return view('survey.expired', ['reason' => 'expired', 'link' => $link]);
        }

        $template = $link->survey->template;
        $items = $template->questions;
        $questions = $items->reject->isSection()->values();
        $view = $link->usesWizard() ? 'survey.wizard' : 'survey.scroll';

        // Issue #46: Nach Bereichen gruppieren; mit „Bereiche als Seiten“ ist jeder Bereich eine Seite.
        $groups = SurveyTemplate::groupBySection($items);
        $paged = $template->section_pages && $items->contains->isSection();

        return view($view, compact('link', 'questions', 'groups', 'paged'));
    }

    /** Antwort speichern + Token sperren. */
    public function submit(Request $request, string $token): RedirectResponse
    {
        $link = SurveyLink::where('token', $token)
            ->with('survey.template.questions')
            ->firstOrFail();

        if (! $link->isValid()) {
            return redirect()->route('survey.start', ['token' => $token]);
        }

        // Issue #46: Bereiche werden nicht beantwortet.
        $questions = $link->survey->template->questions->reject->isSection()->values();

        // Validation-Regeln dynamisch aus Fragetypen ableiten (gemeinsam mit der Vorschau).
        [$rules, $messages] = SurveyTemplate::answerRules($questions);

        $validated = $request->validate($rules, $messages);

        $response = SurveyResponse::create([
            'survey_link_id' => $link->id,
            'submitted_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        foreach ($questions as $question) {
            $raw = $validated['answers'][$question->id] ?? null;

            if ($raw === null) {
                continue;
            }

            $answer = ['survey_response_id' => $response->id, 'survey_template_question_id' => $question->id];

            if ($question->type === 'choice') {
                $choices = array_values($raw['choices'] ?? []);
                $text = $question->hasFreeText() ? trim((string) ($raw['text'] ?? '')) : '';

                if ($choices === [] && $text === '') {
                    continue;
                }

                $answer['choice_answer'] = $choices ?: null;
                $answer['text_answer'] = $text !== '' ? $text : null;
                SurveyAnswer::create($answer);

                continue;
            }

            match ($question->type) {
                'rating', 'smiley' => $answer['rating_answer'] = (int) $raw,
                'text' => $answer['text_answer'] = $raw,
                'yes_no' => $answer['yes_no_answer'] = (bool) $raw,
                default => null,
            };

            SurveyAnswer::create($answer);
        }

        // Token nach Abgabe sperren (kein zweites Ausfüllen möglich)
        $link->update(['completed_at' => now()]);

        return redirect()->route('survey.danke', ['token' => $token]);
    }

    /** Bestätigungsseite mit motivierendem Abschluss-Screen. */
    public function danke(string $token): View
    {
        $link = SurveyLink::where('token', $token)
            ->with('survey.adventure')
            ->firstOrFail();

        return view('survey.danke', compact('link'));
    }
}
