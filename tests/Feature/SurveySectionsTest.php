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
 * Issue #46: Umfragevorlagen – Reihenfolge, Bereiche (auch als Seiten) und
 * Smiley-Zusatzantwort ohne Smiley.
 */
class SurveySectionsTest extends TestCase
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

    /** Vorlage: Bereich „Über dich“, Ja/Nein-Frage, Bereich „Dein Abenteuer“, Smiley mit Zusatzantwort. */
    private function template(string $targetGroup = 'teamer', bool $pages = false): SurveyTemplate
    {
        $template = SurveyTemplate::create([
            'name' => 'Vorlage #46',
            'target_group' => $targetGroup,
            'active' => true,
            'section_pages' => $pages,
            'created_by' => $this->admin->id,
        ]);

        $items = [
            ['section', 'Über dich', ['subtitle' => 'Damit wir unsere Teilnehmer besser kennenlernen.'], false],
            ['yes_no', 'Warst du schon einmal dabei?', [], true],
            ['section', 'Dein Abenteuer', ['subtitle' => 'Wie viel Spaß haben dir die verschiedenen Dinge gemacht?'], false],
            ['smiley', 'Wie hat dir das Bogenschießen gefallen?', ['na_option' => '1'], true],
        ];
        foreach ($items as $i => [$type, $text, $options, $required]) {
            SurveyTemplateQuestion::create([
                'survey_template_id' => $template->id,
                'question_text' => $text,
                'type' => $type,
                'options' => SurveyTemplateQuestion::optionsFromInput($type, $options),
                'sort_order' => $i,
                'required' => $required,
            ]);
        }

        return $template;
    }

    private function link(SurveyTemplate $template, string $targetType = 'teamer'): SurveyLink
    {
        $survey = Survey::create([
            'adventure_id' => Adventure::factory()->create()->id,
            'survey_template_id' => $template->id,
            'title' => 'Umfrage #46',
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

    private function questionId(string $type): int
    {
        return SurveyTemplateQuestion::where('type', $type)->value('id');
    }

    // ── Vorlagen-Editor ───────────────────────────────────────────────

    public function test_vorlage_speichert_bereiche_reihenfolge_und_zusatzantwort(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.surveys.templates.store'), [
                'name' => 'Kinder-Feedback',
                'target_group' => 'participant_child',
                'active' => true,
                'section_pages' => '1',
                // Reihenfolge im Formular = gespeicherte Reihenfolge (nach Verschieben im Editor).
                'questions' => [
                    7 => ['question_text' => 'Über dich', 'type' => 'section', 'subtitle' => 'Damit wir dich kennenlernen.', 'required' => '1'],
                    2 => ['question_text' => 'Wie alt bist du?', 'type' => 'text'],
                    5 => ['question_text' => 'Bogenschießen', 'type' => 'smiley', 'na_option' => '1', 'na_label' => ''],
                ],
            ])
            ->assertRedirect(route('admin.surveys.templates.index'));

        $template = SurveyTemplate::firstOrFail();
        $this->assertTrue($template->section_pages);

        [$section, $text, $smiley] = $template->questions->all();
        $this->assertTrue($section->isSection());
        $this->assertSame('Damit wir dich kennenlernen.', $section->subtitle());
        $this->assertFalse($section->required);
        $this->assertSame('Wie alt bist du?', $text->question_text);
        $this->assertTrue($smiley->hasNaOption());
        $this->assertSame('Habe ich nicht mitgemacht', $smiley->naLabel());

        // Bereiche zählen nicht als Fragen.
        $this->actingAs($this->admin)
            ->get(route('admin.surveys.templates.index'))
            ->assertOk()
            ->assertSeeInOrder(['Kinder-Feedback', '2']);
    }

    public function test_editor_zeigt_bereich_und_verschiebe_buttons(): void
    {
        $template = $this->template();

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.templates.edit', $template))
            ->assertOk()
            ->assertSee('+ Bereich hinzufügen')
            ->assertSee('Bereiche als einzelne Seiten anzeigen')
            ->assertSee('Bereich nach oben verschieben')
            ->assertSee('Frage nach unten verschieben')
            ->assertSee('Zusätzliche Antwort ohne Smiley')
            ->assertSee('value="Über dich"', false);
    }

    // ── Ausfüllen ────────────────────────────────────────────────────

    public function test_scroll_formular_zeigt_nummerierte_bereiche(): void
    {
        $link = $this->link($this->template());

        $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertSeeInOrder([
                '1. Über dich',
                'Damit wir unsere Teilnehmer besser kennenlernen.',
                'Warst du schon einmal dabei?',
                '2. Dein Abenteuer',
                'Wie hat dir das Bogenschießen gefallen?',
                'Habe ich nicht mitgemacht',
            ])
            ->assertSee('Frage 0 von 2')
            ->assertDontSee('data-page-go="', false);

        $this->get(route('survey.start', $link->token))->assertSee('2 Fragen');
    }

    public function test_bereiche_als_seiten(): void
    {
        $link = $this->link($this->template(pages: true));

        $html = $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertSee('Seite 1 von 2')
            ->assertSee('data-page-go="2"', false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'class="survey-page"'));
    }

    public function test_wizard_mit_seiten_zeigt_einen_schritt_je_bereich(): void
    {
        $link = $this->link($this->template('participant_child', pages: true), 'participant_child');

        $html = $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertSee('Seite 1 von 2')
            ->assertSee('1. Über dich')
            ->getContent();

        $this->assertSame(2, substr_count($html, 'class="survey-step '));
    }

    public function test_wizard_ohne_seiten_eine_frage_je_schritt_mit_bereichstitel(): void
    {
        $link = $this->link($this->template('participant_child'), 'participant_child');

        $html = $this->get(route('survey.show', $link->token))
            ->assertOk()
            ->assertSee('Frage 1 von 2')
            ->assertSee('2. Dein Abenteuer')
            ->getContent();

        $this->assertSame(2, substr_count($html, 'class="survey-step '));
    }

    public function test_zusatzantwort_wird_als_null_gespeichert_und_zaehlt_als_antwort(): void
    {
        $link = $this->link($this->template());

        $this->post(route('survey.submit', $link->token), ['answers' => [
            $this->questionId('yes_no') => '1',
            $this->questionId('smiley') => '0',
        ]])->assertRedirect(route('survey.danke', $link->token));

        $this->assertSame(0, SurveyAnswer::where('survey_template_question_id', $this->questionId('smiley'))->value('rating_answer'));
        // Bereiche werden nicht beantwortet.
        $this->assertSame(2, SurveyAnswer::count());
    }

    public function test_null_ohne_aktivierte_zusatzantwort_ist_ungueltig(): void
    {
        $template = $this->template();
        $smiley = SurveyTemplateQuestion::where('type', 'smiley')->firstOrFail();
        $smiley->update(['options' => SurveyTemplateQuestion::optionsFromInput('smiley', [])]);
        $link = $this->link($template);

        $this->post(route('survey.submit', $link->token), ['answers' => [
            $this->questionId('yes_no') => '1',
            $smiley->id => '0',
        ]])->assertSessionHasErrors("answers.{$smiley->id}");
    }

    // ── Auswertung ───────────────────────────────────────────────────

    public function test_auswertung_zaehlt_zusatzantwort_nicht_im_durchschnitt(): void
    {
        $template = $this->template();
        $smileyId = $this->questionId('smiley');
        $yesNoId = $this->questionId('yes_no');

        foreach (['4', '2', '0'] as $value) {
            $link = $this->link($template);
            $this->post(route('survey.submit', $link->token), ['answers' => [$yesNoId => '1', $smileyId => $value]]);
        }

        $survey = Survey::latest('id')->firstOrFail();
        $response = $this->actingAs($this->admin)
            ->get(route('admin.surveys.results', $survey))
            ->assertOk();

        $stats = collect($response->viewData('stats'));
        $smileyStat = $stats->first(fn ($s) => isset($s['question']) && $s['question']->id === $smileyId);

        // Diese Umfrage enthält nur die letzte Antwort (0) – Durchschnitt leer, Zusatzantwort 1×.
        $this->assertNull($smileyStat['average']);
        $this->assertSame(1, $smileyStat['na_count']);
        $this->assertSame(2, $stats->filter(fn ($s) => isset($s['section']))->count());

        $response->assertSee('1. Über dich')->assertSee('Habe ich nicht mitgemacht');
    }

    public function test_durchschnitt_ohne_zusatzantworten(): void
    {
        $template = $this->template();
        $link = $this->link($template);
        $survey = $link->survey;
        $smileyId = $this->questionId('smiley');
        $yesNoId = $this->questionId('yes_no');

        $this->post(route('survey.submit', $link->token), ['answers' => [$yesNoId => '1', $smileyId => '4']]);
        foreach (['2', '0'] as $value) {
            $extra = SurveyLink::create(['survey_id' => $survey->id, 'target_type' => 'teamer', 'name' => 'X', 'email' => 'x'.$value.'@example.test']);
            $this->post(route('survey.submit', $extra->token), ['answers' => [$yesNoId => '1', $smileyId => $value]]);
        }

        $stats = collect($this->actingAs($this->admin)->get(route('admin.surveys.results', $survey))->viewData('stats'));
        $smileyStat = $stats->first(fn ($s) => isset($s['question']) && $s['question']->id === $smileyId);

        $this->assertEquals(3.0, $smileyStat['average']);
        $this->assertSame(1, $smileyStat['na_count']);
        $this->assertSame(3, $smileyStat['count']);
    }

    public function test_einzelantwort_und_pdf_zeigen_bereiche_und_zusatzantwort(): void
    {
        $link = $this->link($this->template());
        $this->post(route('survey.submit', $link->token), ['answers' => [
            $this->questionId('yes_no') => '0',
            $this->questionId('smiley') => '0',
        ]]);

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.links.response', [$link->survey, $link]))
            ->assertOk()
            ->assertSeeInOrder(['1. Über dich', 'Warst du schon einmal dabei?', '2. Dein Abenteuer', 'Habe ich nicht mitgemacht'])
            ->assertDontSee('(0 von 5)');

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.export.pdf', $link->survey))
            ->assertOk();
    }
}
