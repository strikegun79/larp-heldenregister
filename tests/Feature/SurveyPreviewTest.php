<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyLink;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vorschau von Umfragen und Vorlagen: echtes Formular, Antworten werden nie gespeichert. */
class SurveyPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(10);
    }

    private function template(string $targetGroup = 'teamer'): SurveyTemplate
    {
        $template = SurveyTemplate::create([
            'name' => 'Feedback-Vorlage',
            'target_group' => $targetGroup,
            'active' => true,
            'created_by' => $this->admin->id,
        ]);
        SurveyTemplateQuestion::create([
            'survey_template_id' => $template->id,
            'question_text' => 'Hat es dir gefallen?',
            'type' => 'yes_no',
            'sort_order' => 0,
            'required' => true,
        ]);

        return $template;
    }

    private function survey(SurveyTemplate $template): Survey
    {
        return Survey::create([
            'adventure_id' => Adventure::factory()->create(['name' => 'Sommerlager'])->id,
            'survey_template_id' => $template->id,
            'title' => 'Sommerlager-Umfrage',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    private function questionId(): int
    {
        return SurveyTemplateQuestion::value('id');
    }

    public function test_umfrage_vorschau_zeigt_formular_mit_hinweis(): void
    {
        $survey = $this->survey($this->template());

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.preview', $survey))
            ->assertOk()
            ->assertSee('Vorschau – deine Antworten werden nicht gespeichert.')
            ->assertSee('Hat es dir gefallen?')
            ->assertSee('Sommerlager-Umfrage')
            ->assertSee('action="'.route('admin.surveys.preview.submit', $survey).'"', false);

        $this->assertSame(0, SurveyLink::count());
    }

    public function test_abschicken_prueft_und_verwirft(): void
    {
        $survey = $this->survey($this->template());

        // Pflichtfrage fehlt → Fehlermeldung wie beim echten Ausfüllen.
        $this->actingAs($this->admin)
            ->from(route('admin.surveys.preview', $survey))
            ->post(route('admin.surveys.preview.submit', $survey), ['answers' => []])
            ->assertRedirect(route('admin.surveys.preview', $survey))
            ->assertSessionHasErrors('answers.'.$this->questionId());

        $this->actingAs($this->admin)
            ->post(route('admin.surveys.preview.submit', $survey), ['answers' => [$this->questionId() => '1']])
            ->assertOk()
            ->assertSee('Vorschau – deine Antworten werden nicht gespeichert.')
            ->assertSee('Zurück zur Verwaltung');

        $this->assertSame(0, SurveyLink::count());
        $this->assertSame(0, SurveyResponse::count());
        $this->assertSame(0, SurveyAnswer::count());
    }

    public function test_vorlagen_vorschau_mit_wahl_der_ansicht(): void
    {
        $template = $this->template('all');

        // „Alle Gruppen“: standardmäßig Kinder-Ansicht (Wizard).
        $this->actingAs($this->admin)
            ->get(route('admin.surveys.templates.preview', $template))
            ->assertOk()
            ->assertSee('survey-form-wrap--child', false)
            ->assertSee('Beispiel-Veranstaltung');

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.templates.preview', [$template, 'as' => 'parent']))
            ->assertOk()
            ->assertSee('survey-form-wrap--adult', false);

        $this->actingAs($this->admin)
            ->post(route('admin.surveys.templates.preview.submit', [$template, 'as' => 'parent']), ['answers' => [$this->questionId() => '0']])
            ->assertOk()
            ->assertSee('Herzlichen Dank!');

        $this->assertSame(0, Survey::count());
        $this->assertSame(0, SurveyAnswer::count());
    }

    public function test_buttons_in_auswertung_und_vorlagenliste(): void
    {
        $template = $this->template();
        $survey = $this->survey($template);

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.results', $survey))
            ->assertOk()
            ->assertSee(route('admin.surveys.preview', $survey), false);

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.templates.index'))
            ->assertOk()
            ->assertSee(route('admin.surveys.templates.preview', $template), false);
    }

    public function test_ohne_survey_admin_keine_vorschau(): void
    {
        $survey = $this->survey($this->template());
        $user = User::factory()->create();
        $user->roles()->attach(70); // Teilnehmer

        $this->actingAs($user)->get(route('admin.surveys.preview', $survey))->assertForbidden();
        $this->actingAs($user)->post(route('admin.surveys.preview.submit', $survey))->assertForbidden();
        $this->actingAs($user)->get(route('admin.surveys.templates.preview', $survey->template))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.surveys.preview', $survey))->assertRedirect(route('login'));
    }
}
