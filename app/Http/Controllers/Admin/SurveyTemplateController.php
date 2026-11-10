<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Umfrage-Vorlagenverwaltung (SURV-01): Fragenvorlagen anlegen und bearbeiten.
 * Ausschließlich survey.admin – Projektleiter mit survey.view dürfen NICHT editieren.
 */
class SurveyTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'can:survey.admin']);
    }

    public function index(): View
    {
        $templates = SurveyTemplate::withCount('questions')
            ->with('creator')
            ->orderBy('target_group')
            ->orderBy('name')
            ->get();

        return view('admin.surveys.templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('admin.surveys.templates.edit', [
            'template'  => new SurveyTemplate,
            'questions' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'target_group' => ['required', 'in:' . implode(',', array_keys(SurveyTemplate::TARGET_GROUPS))],
            'active'       => ['boolean'],
            'questions'                  => ['array'],
            'questions.*.question_text'  => ['required', 'string', 'max:1000'],
            'questions.*.type'           => ['required', 'in:rating,text,yes_no'],
            'questions.*.required'       => ['boolean'],
        ]);

        $template = SurveyTemplate::create([
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'target_group' => $data['target_group'],
            'active'       => $data['active'] ?? true,
            'created_by'   => auth()->id(),
        ]);

        $this->syncQuestions($template, $data['questions'] ?? []);

        return redirect()->route('admin.surveys.templates.index')
            ->with('success', 'Vorlage erstellt.');
    }

    public function edit(SurveyTemplate $template): View
    {
        $template->load('questions');

        return view('admin.surveys.templates.edit', [
            'template'  => $template,
            'questions' => $template->questions,
        ]);
    }

    public function update(Request $request, SurveyTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'target_group' => ['required', 'in:' . implode(',', array_keys(SurveyTemplate::TARGET_GROUPS))],
            'active'       => ['boolean'],
            'questions'                  => ['array'],
            'questions.*.question_text'  => ['required', 'string', 'max:1000'],
            'questions.*.type'           => ['required', 'in:rating,text,yes_no'],
            'questions.*.required'       => ['boolean'],
        ]);

        $template->update([
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'target_group' => $data['target_group'],
            'active'       => $data['active'] ?? true,
        ]);

        $this->syncQuestions($template, $data['questions'] ?? []);

        return redirect()->route('admin.surveys.templates.index')
            ->with('success', 'Vorlage aktualisiert.');
    }

    public function destroy(SurveyTemplate $template): RedirectResponse
    {
        // Vorlagen mit bereits versendeten Umfragen nicht löschen
        if ($template->surveys()->exists()) {
            return back()->with('error', 'Vorlage wird von aktiven Umfragen verwendet und kann nicht gelöscht werden.');
        }

        $template->delete();

        return redirect()->route('admin.surveys.templates.index')
            ->with('success', 'Vorlage gelöscht.');
    }

    private function syncQuestions(SurveyTemplate $template, array $questions): void
    {
        $template->questions()->delete();

        foreach ($questions as $index => $q) {
            SurveyTemplateQuestion::create([
                'survey_template_id' => $template->id,
                'question_text'      => $q['question_text'],
                'type'               => $q['type'],
                'sort_order'         => $index,
                'required'           => (bool) ($q['required'] ?? false),
            ]);
        }
    }
}
