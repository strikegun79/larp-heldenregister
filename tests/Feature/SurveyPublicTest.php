<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Survey;
use App\Models\User;
use App\Models\SurveyAnswer;
use App\Models\SurveyLink;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SURV-PUB: Öffentlicher Umfrage-Flow – start, show (wizard/scroll), submit, danke.
 */
class SurveyPublicTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
        $this->creator = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Hilfsmethoden
    // -------------------------------------------------------------------------

    private function template(string $targetGroup = 'teamer'): SurveyTemplate
    {
        return SurveyTemplate::create([
            'name'         => 'Testvorlage',
            'target_group' => $targetGroup,
            'active'       => true,
            'created_by'   => $this->creator->id,
        ]);
    }

    private function question(SurveyTemplate $template, string $type = 'rating', bool $required = true): SurveyTemplateQuestion
    {
        return SurveyTemplateQuestion::create([
            'survey_template_id' => $template->id,
            'question_text'      => 'Wie war das Event?',
            'type'               => $type,
            'sort_order'         => 0,
            'required'           => $required,
        ]);
    }

    private function survey(SurveyTemplate $template): Survey
    {
        $adventure = Adventure::factory()->create();

        return Survey::create([
            'adventure_id'       => $adventure->id,
            'survey_template_id' => $template->id,
            'title'              => 'Test-Umfrage',
            'status'             => 'active',
            'created_by'         => $this->creator->id,
        ]);
    }

    private function link(Survey $survey, string $targetType = 'teamer', array $attrs = []): SurveyLink
    {
        return SurveyLink::create(array_merge([
            'survey_id'   => $survey->id,
            'target_type' => $targetType,
            'name'        => 'Test Person',
            'email'       => 'test@example.test',
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // start
    // -------------------------------------------------------------------------

    public function test_start_mit_gueltigem_token_zeigt_begruessung(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey);

        $this->get(route('survey.start', $link->token))
            ->assertOk()
            ->assertViewIs('survey.start');
    }

    public function test_start_mit_ungueltigem_token_gibt_404(): void
    {
        $this->get(route('survey.start', 'ungueltig-xyz'))
            ->assertNotFound();
    }

    public function test_start_bei_abgeschlossenem_link_zeigt_expired(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, 'teamer', ['completed_at' => now()]);

        $this->get(route('survey.start', $link->token))
            ->assertOk()
            ->assertViewIs('survey.expired')
            ->assertViewHas('reason', 'completed');
    }

    public function test_start_bei_abgelaufenem_link_zeigt_expired(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, 'teamer', ['expires_at' => now()->subDay()]);

        $this->get(route('survey.start', $link->token))
            ->assertOk()
            ->assertViewIs('survey.expired')
            ->assertViewHas('reason', 'expired');
    }

    // -------------------------------------------------------------------------
    // show (Formular – Wizard vs. Scroll)
    // -------------------------------------------------------------------------

    public function test_show_fuer_teamer_zeigt_scroll_ansicht(): void
    {
        $tmpl   = $this->template('teamer');
        $this->question($tmpl);
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, 'teamer');

        $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertViewIs('survey.scroll');
    }

    public function test_show_fuer_kinder_zeigt_wizard_ansicht(): void
    {
        $tmpl   = $this->template('participant_child');
        $this->question($tmpl);
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, 'participant_child');

        $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertViewIs('survey.wizard');
    }

    public function test_show_bei_abgeschlossenem_link_zeigt_expired(): void
    {
        $tmpl   = $this->template();
        $this->question($tmpl);
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, 'teamer', ['completed_at' => now()]);

        $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertViewIs('survey.expired');
    }

    // -------------------------------------------------------------------------
    // submit
    // -------------------------------------------------------------------------

    public function test_submit_speichert_antworten_und_sperrt_link(): void
    {
        $tmpl     = $this->template();
        $question = $this->question($tmpl, 'rating');
        $survey   = $this->survey($tmpl);
        $link     = $this->link($survey);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [$question->id => 8],
        ])->assertRedirect(route('survey.danke', $link->token));

        $this->assertNotNull($link->fresh()->completed_at);
        $this->assertDatabaseHas('survey_responses', ['survey_link_id' => $link->id]);
        $this->assertDatabaseHas('survey_answers', [
            'survey_template_question_id' => $question->id,
            'rating_answer'               => 8,
        ]);
    }

    public function test_submit_speichert_text_antwort(): void
    {
        $tmpl     = $this->template();
        $question = $this->question($tmpl, 'text');
        $survey   = $this->survey($tmpl);
        $link     = $this->link($survey);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [$question->id => 'Hat super Spaß gemacht!'],
        ])->assertRedirect(route('survey.danke', $link->token));

        $this->assertDatabaseHas('survey_answers', [
            'text_answer' => 'Hat super Spaß gemacht!',
        ]);
    }

    public function test_submit_speichert_ja_nein_antwort(): void
    {
        $tmpl     = $this->template();
        $question = $this->question($tmpl, 'yes_no');
        $survey   = $this->survey($tmpl);
        $link     = $this->link($survey);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [$question->id => '1'],
        ])->assertRedirect(route('survey.danke', $link->token));

        $this->assertDatabaseHas('survey_answers', ['yes_no_answer' => true]);
    }

    public function test_submit_validiert_pflichtfrage(): void
    {
        $tmpl     = $this->template();
        $question = $this->question($tmpl, 'rating', true); // required
        $survey   = $this->survey($tmpl);
        $link     = $this->link($survey);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [], // Pflichtfrage fehlt
        ])->assertSessionHasErrors("answers.{$question->id}");

        $this->assertNull($link->fresh()->completed_at);
    }

    public function test_submit_bei_abgeschlossenem_link_leitet_zu_start(): void
    {
        $tmpl   = $this->template();
        $this->question($tmpl, 'rating');
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, 'teamer', ['completed_at' => now()]);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [],
        ])->assertRedirect(route('survey.start', $link->token));

        // Keine neue Response angelegt
        $this->assertDatabaseCount('survey_responses', 0);
    }

    // -------------------------------------------------------------------------
    // danke
    // -------------------------------------------------------------------------

    public function test_danke_zeigt_bestaetigungsseite(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, 'teamer', ['completed_at' => now()]);

        $this->get(route('survey.danke', $link->token))
            ->assertOk()
            ->assertViewIs('survey.danke');
    }
}
