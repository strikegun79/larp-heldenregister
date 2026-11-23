<?php

namespace App\Http\Controllers;

use App\Models\SurveyAnswer;
use App\Models\SurveyLink;
use App\Models\SurveyResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $questions = $link->survey->template->questions;
        $view = $link->usesWizard() ? 'survey.wizard' : 'survey.scroll';

        return view($view, compact('link', 'questions'));
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

        $questions = $link->survey->template->questions;

        // Validation-Regeln dynamisch aus Fragetypen ableiten
        $rules = [];
        $messages = [];
        foreach ($questions as $question) {
            $key = "answers.{$question->id}";
            $requiredRule = $question->required ? 'required' : 'nullable';

            // Issue #24: Optionen (Einfach-/Mehrfachauswahl) plus optionale Nachricht.
            if ($question->type === 'choice') {
                $choicesRule = ['nullable', 'array'];
                if ($question->required) {
                    $choicesRule[0] = $question->hasFreeText() ? "required_without:{$key}.text" : 'required';
                }
                if (! $question->allowsMultiple()) {
                    $choicesRule[] = 'max:1';
                }

                $rules[$key] = ['nullable', 'array'];
                $rules["{$key}.choices"] = $choicesRule;
                $rules["{$key}.choices.*"] = ['string', Rule::in($question->choices())];
                $rules["{$key}.text"] = ['nullable', 'string', 'max:2000'];

                $messages["{$key}.choices.required"] = 'Bitte wähle eine Antwort aus.';
                $messages["{$key}.choices.required_without"] = 'Bitte wähle eine Antwort aus oder schreib eine Nachricht.';
                $messages["{$key}.choices.max"] = 'Bitte wähle nur eine Antwort aus.';
                $messages["{$key}.choices.*.in"] = 'Diese Antwort gibt es bei der Frage nicht.';

                continue;
            }

            $rules[$key] = match ($question->type) {
                'rating' => [$requiredRule, 'integer', 'min:1', 'max:10'],
                'smiley' => [$requiredRule, 'integer', 'min:1', 'max:5'],
                'text' => [$requiredRule, 'string', 'max:2000'],
                'yes_no' => [$requiredRule, 'in:1,0'],
                default => [$requiredRule],
            };
        }

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
