<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyLink;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #24: Fragetypen „Optionen mit Freitext“ (choice) und „Smileys 1–5“ (smiley).
 */
class SurveyChoiceSmileyTest extends TestCase
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

    private function template(): SurveyTemplate
    {
        return SurveyTemplate::create([
            'name' => 'Vorlage #24',
            'target_group' => 'teamer',
            'active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    private function question(SurveyTemplate $template, string $type, array $options = [], bool $required = true): SurveyTemplateQuestion
    {
        return SurveyTemplateQuestion::create([
            'survey_template_id' => $template->id,
            'question_text' => $type === 'choice' ? 'Bist du beim Aufbau dabei?' : 'Wie viel Spaß hattest du?',
            'type' => $type,
            'options' => SurveyTemplateQuestion::optionsFromInput($type, $options),
            'sort_order' => 0,
            'required' => $required,
        ]);
    }

    private function link(SurveyTemplate $template, string $targetType = 'teamer'): SurveyLink
    {
        $survey = Survey::create([
            'adventure_id' => Adventure::factory()->create()->id,
            'survey_template_id' => $template->id,
            'title' => 'Umfrage #24',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        return SurveyLink::create([
            'survey_id' => $survey->id,
            'target_type' => $targetType,
            'name' => 'Test Person',
            'email' => 'test@example.test',
        ]);
    }

    // ── Vorlagen-Editor ───────────────────────────────────────────────

    public function test_vorlage_speichert_optionen_und_smiley_texte(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.surveys.templates.store'), [
                'name' => 'Teamer-Abfrage',
                'target_group' => 'teamer',
                'active' => true,
                'questions' => [
                    [
                        'question_text' => 'Kommst du zum Aufbau?',
                        'type' => 'choice',
                        'required' => true,
                        'choices' => "Bin dabei\n\n  Komme später \nSchaffe es nicht\nBin dabei",
                        'multiple' => '1',
                        // free_text nicht angehakt
                    ],
                    [
                        'question_text' => 'Wie viel hast du gelernt?',
                        'type' => 'smiley',
                        'labels' => [5 => 'Unglaublich viel', 4 => '', 3 => 'Mittel', 2 => 'Wenig', 1 => 'Nichts'],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.surveys.templates.index'));

        [$choice, $smiley] = SurveyTemplateQuestion::orderBy('sort_order')->get()->all();

        $this->assertSame(['Bin dabei', 'Komme später', 'Schaffe es nicht'], $choice->choices());
        $this->assertTrue($choice->allowsMultiple());
        $this->assertFalse($choice->hasFreeText());

        $this->assertSame(
            [5 => 'Unglaublich viel', 4 => 'Viel', 3 => 'Mittel', 2 => 'Wenig', 1 => 'Nichts'],
            $smiley->smileyLabels()
        );
        $this->assertNull(SurveyTemplateQuestion::optionsFromInput('rating', []));
    }

    public function test_leere_optionen_fallen_auf_die_vorauswahl_zurueck(): void
    {
        $options = SurveyTemplateQuestion::optionsFromInput('choice', ['choices' => "  \n"]);

        $this->assertSame(SurveyTemplateQuestion::DEFAULT_CHOICES, $options['choices']);
        $this->assertSame(
            SurveyTemplateQuestion::DEFAULT_SMILEY_LABELS,
            SurveyTemplateQuestion::optionsFromInput('smiley', [])['labels']
        );
    }

    public function test_unbekannter_fragetyp_wird_abgelehnt(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.surveys.templates.store'), [
                'name' => 'X',
                'target_group' => 'teamer',
                'questions' => [['question_text' => 'Frage', 'type' => 'zauberei']],
            ])
            ->assertSessionHasErrors('questions.0.type');
    }

    public function test_editor_zeigt_neue_typen_und_vorlage_fuer_neue_fragen(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.surveys.templates.create'))
            ->assertOk()
            ->assertSee('Optionen mit Freitext')
            ->assertSee('Smileys (1–5)')
            ->assertSee('id="question-template"', false)
            ->assertSee('questions[__INDEX__][choices]', false)
            ->assertSee('Mehrfachauswahl erlaubt');
    }

    // ── Ausfüllen ─────────────────────────────────────────────────────

    public function test_formular_zeigt_optionen_und_smileys_in_beiden_ansichten(): void
    {
        $template = $this->template();
        $this->question($template, 'choice', ['choices' => "Bin dabei\nKomme später", 'free_text' => '1']);
        $this->question($template, 'smiley', ['labels' => [5 => 'Riesig']]);

        foreach (['teamer', 'participant_child'] as $targetType) {
            $link = $this->link($template, $targetType);

            $this->get(route('survey.show', $link->token))
                ->assertOk()
                ->assertSee('type="radio"', false)
                ->assertSee('Komme später')
                ->assertSee('Deine Nachricht')
                ->assertSee('🤩')
                ->assertSee('Riesig')
                ->assertSee('Gar keinen');
        }
    }

    public function test_mehrfachauswahl_nutzt_checkboxen(): void
    {
        $template = $this->template();
        $this->question($template, 'choice', ['multiple' => '1']);
        $link = $this->link($template);

        $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertSee('type="checkbox"', false)
            ->assertDontSee('Deine Nachricht');
    }

    public function test_antworten_werden_gespeichert(): void
    {
        $template = $this->template();
        $choice = $this->question($template, 'choice', ['multiple' => '1', 'free_text' => '1']);
        $smiley = $this->question($template, 'smiley');
        $link = $this->link($template);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [
                $choice->id => ['choices' => ['Bin dabei', 'Komme später'], 'text' => 'Ich bringe Kuchen mit.'],
                $smiley->id => '4',
            ],
        ])->assertRedirect(route('survey.danke', $link->token));

        $choiceAnswer = SurveyAnswer::where('survey_template_question_id', $choice->id)->firstOrFail();
        $this->assertSame(['Bin dabei', 'Komme später'], $choiceAnswer->choice_answer);
        $this->assertSame('Ich bringe Kuchen mit.', $choiceAnswer->text_answer);

        $this->assertSame(4, (int) SurveyAnswer::where('survey_template_question_id', $smiley->id)->value('rating_answer'));
    }

    public function test_pflichtfrage_akzeptiert_nur_nachricht_ohne_option(): void
    {
        $template = $this->template();
        $choice = $this->question($template, 'choice', ['free_text' => '1']);
        $link = $this->link($template);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [$choice->id => ['text' => 'Ich weiß es noch nicht.']],
        ])->assertRedirect(route('survey.danke', $link->token));

        $answer = SurveyAnswer::firstOrFail();
        $this->assertNull($answer->choice_answer);
        $this->assertSame('Ich weiß es noch nicht.', $answer->text_answer);
    }

    public function test_pflichtfrage_ohne_antwort_wird_abgelehnt(): void
    {
        $template = $this->template();
        $choice = $this->question($template, 'choice', ['free_text' => '1']);
        $smiley = $this->question($template, 'smiley');
        $link = $this->link($template);

        $this->post(route('survey.submit', $link->token), ['answers' => []])
            ->assertSessionHasErrors(["answers.{$choice->id}.choices", "answers.{$smiley->id}"]);

        $this->assertNull($link->fresh()->completed_at);
    }

    public function test_ungueltige_eingaben_werden_abgelehnt(): void
    {
        $template = $this->template();
        $single = $this->question($template, 'choice');
        $smiley = $this->question($template, 'smiley');
        $link = $this->link($template);

        // Unbekannte Option.
        $this->post(route('survey.submit', $link->token), [
            'answers' => [$single->id => ['choices' => ['Ich bin ein Drache']], $smiley->id => '3'],
        ])->assertSessionHasErrors("answers.{$single->id}.choices.0");

        // Zwei Optionen bei Einfachauswahl, Smiley außerhalb 1–5.
        $this->post(route('survey.submit', $link->token), [
            'answers' => [$single->id => ['choices' => ['Bin dabei', 'Komme später']], $smiley->id => '6'],
        ])->assertSessionHasErrors(["answers.{$single->id}.choices", "answers.{$smiley->id}"]);

        $this->assertSame(0, SurveyAnswer::count());
    }

    public function test_nachricht_wird_ohne_freitextfeld_verworfen(): void
    {
        $template = $this->template();
        $choice = $this->question($template, 'choice', ['free_text' => '0']);
        $link = $this->link($template);

        $this->post(route('survey.submit', $link->token), [
            'answers' => [$choice->id => ['choices' => ['Bin dabei'], 'text' => 'eingeschmuggelt']],
        ])->assertRedirect(route('survey.danke', $link->token));

        $this->assertNull(SurveyAnswer::firstOrFail()->text_answer);
    }

    // ── Auswertung ────────────────────────────────────────────────────

    private function answeredSurvey(): array
    {
        $template = $this->template();
        $choice = $this->question($template, 'choice', ['free_text' => '1']);
        $smiley = $this->question($template, 'smiley');
        $links = [];

        foreach ([['Bin dabei', 5, 'Freue mich!'], ['Bin dabei', 4, null], ['Schaffe es nicht', 1, null]] as [$option, $points, $text]) {
            $link = $this->link($template);
            // Alle Links gehören zur selben Umfrage.
            if ($links) {
                $link->update(['survey_id' => $links[0]->survey_id]);
            }
            $this->post(route('survey.submit', $link->token), [
                'answers' => [
                    $choice->id => ['choices' => [$option], 'text' => $text],
                    $smiley->id => (string) $points,
                ],
            ])->assertRedirect();
            $links[] = $link->fresh();
        }

        return [$links[0]->survey, $links[0]];
    }

    public function test_auswertung_zaehlt_optionen_und_smileys(): void
    {
        [$survey] = $this->answeredSurvey();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.surveys.results', $survey))
            ->assertOk()
            ->assertSee('Freue mich!')
            ->assertSee('Ø von 5')
            ->assertSee('3,3');

        $stats = collect($response->viewData('stats'));
        $choiceStat = $stats->firstWhere('question.type', 'choice');
        $this->assertSame(['Bin dabei' => 2, 'Komme später' => 0, 'Schaffe es nicht' => 1], $choiceStat['choice_counts']);

        $smileyStat = $stats->firstWhere('question.type', 'smiley');
        $this->assertSame(1, $smileyStat['distribution'][5]);
        $this->assertSame(1, $smileyStat['distribution'][1]);
    }

    public function test_einzelantwort_und_pdf_zeigen_die_antworten(): void
    {
        [$survey, $link] = $this->answeredSurvey();

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.links.response', [$survey, $link]))
            ->assertOk()
            ->assertSee('Bin dabei')
            ->assertSee('🤩 Sehr viel')
            ->assertSee('Freue mich!');

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.export.pdf', $survey))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
