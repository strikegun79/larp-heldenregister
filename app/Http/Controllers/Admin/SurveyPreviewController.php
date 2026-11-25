<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Adventure;
use App\Models\Survey;
use App\Models\SurveyLink;
use App\Models\SurveyTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vorschau einer Umfrage bzw. Vorlage (nur survey.admin).
 * Zeigt das echte Formular; Antworten werden geprüft, aber nie gespeichert –
 * es entstehen weder Links noch Antworten oder Rücklauf.
 */
class SurveyPreviewController extends Controller
{
    /** Vorschau einer angelegten Umfrage. */
    public function survey(Request $request, Survey $survey): View
    {
        $this->authorize('survey.admin');
        $survey->load(['template.questions', 'adventure']);

        return $this->form($request, $survey, route('admin.surveys.preview.submit', $survey), route('admin.surveys.results', $survey));
    }

    public function submitSurvey(Request $request, Survey $survey): View
    {
        $this->authorize('survey.admin');
        $survey->load(['template.questions', 'adventure']);

        return $this->submit($request, $survey, route('admin.surveys.preview', [$survey, 'as' => $this->targetType($request, $survey->template)]), route('admin.surveys.results', $survey));
    }

    /** Vorschau einer Vorlage (noch ohne Umfrage). */
    public function template(Request $request, SurveyTemplate $template): View
    {
        $this->authorize('survey.admin');

        return $this->form($request, $this->previewSurvey($template), route('admin.surveys.templates.preview.submit', $template), route('admin.surveys.templates.index'));
    }

    public function submitTemplate(Request $request, SurveyTemplate $template): View
    {
        $this->authorize('survey.admin');

        return $this->submit($request, $this->previewSurvey($template), route('admin.surveys.templates.preview', [$template, 'as' => $this->targetType($request, $template)]), route('admin.surveys.templates.index'));
    }

    private function form(Request $request, Survey $survey, string $formAction, string $backUrl): View
    {
        $template = $survey->template;
        $link = $this->previewLink($request, $survey);

        $items = $template->questions;
        $questions = $items->reject->isSection()->values();
        $groups = SurveyTemplate::groupBySection($items);
        $paged = $template->section_pages && $items->contains->isSection();

        return view($link->usesWizard() ? 'survey.wizard' : 'survey.scroll', [
            'link' => $link,
            'questions' => $questions,
            'groups' => $groups,
            'paged' => $paged,
            'preview' => [
                'action' => $formAction,
                'back' => $backUrl,
                'as' => $link->target_type,
                'url' => $request->url(),
            ],
        ]);
    }

    /** Antworten wie beim echten Abschicken prüfen – dann verwerfen. */
    private function submit(Request $request, Survey $survey, string $againUrl, string $backUrl): View
    {
        $questions = $survey->template->questions->reject->isSection()->values();
        [$rules, $messages] = SurveyTemplate::answerRules($questions);
        $request->validate($rules, $messages);

        return view('survey.danke', [
            'link' => $this->previewLink($request, $survey),
            'preview' => ['again' => $againUrl, 'back' => $backUrl],
        ]);
    }

    /** Nicht gespeicherter Link mit der gewählten Zielgruppe (steuert Wizard/Formular und Texte). */
    private function previewLink(Request $request, Survey $survey): SurveyLink
    {
        $link = new SurveyLink(['target_type' => $this->targetType($request, $survey->template), 'name' => 'Vorschau']);
        $link->token = 'vorschau';
        $link->setRelation('survey', $survey);

        return $link;
    }

    /** Zielgruppe der Vorschau: gewählt (?as=) oder die der Vorlage; bei „Alle Gruppen“ Kinder. */
    private function targetType(Request $request, SurveyTemplate $template): string
    {
        $groups = array_keys(self::previewGroups());
        $as = (string) $request->input('as', '');

        if (in_array($as, $groups, true)) {
            return $as;
        }

        return in_array($template->target_group, $groups, true) ? $template->target_group : 'participant_child';
    }

    /**
     * Zielgruppen, deren Ansicht in der Vorschau gewählt werden kann.
     *
     * @return array<string, string>
     */
    public static function previewGroups(): array
    {
        return array_diff_key(SurveyTemplate::TARGET_GROUPS, ['all' => true]);
    }

    /** Nicht gespeicherte Umfrage für die Vorlagen-Vorschau. */
    private function previewSurvey(SurveyTemplate $template): Survey
    {
        $template->load('questions');

        $survey = new Survey(['title' => $template->name]);
        $survey->setRelation('template', $template);
        $survey->setRelation('adventure', new Adventure(['name' => 'Beispiel-Veranstaltung']));

        return $survey;
    }
}
