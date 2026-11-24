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

/**
 * Issue #35: Vorlagen mit vorhandenen Antworten bleiben bearbeitbar –
 * Fragen werden aktualisiert statt gelöscht, beantwortete Fragen archiviert.
 */
class SurveyTemplateSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SurveyTemplate $template;

    private Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(10);

        $this->template = SurveyTemplate::create([
            'name' => 'Teamer-Feedback',
            'target_group' => 'teamer',
            'active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->survey = Survey::create([
            'adventure_id' => Adventure::factory()->create()->id,
            'survey_template_id' => $this->template->id,
            'title' => 'Umfrage #35',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    private function question(string $text, string $type = 'rating', int $sort = 0): SurveyTemplateQuestion
    {
        return SurveyTemplateQuestion::create([
            'survey_template_id' => $this->template->id,
            'question_text' => $text,
            'type' => $type,
            'sort_order' => $sort,
            'required' => false,
        ]);
    }

    private function link(): SurveyLink
    {
        return SurveyLink::create([
            'survey_id' => $this->survey->id,
            'target_type' => 'teamer',
            'name' => 'Test Person',
            'email' => 'test@example.test',
        ]);
    }

    /** Antwort auf eine Frage speichern (abgeschlossener Link). */
    private function answer(SurveyTemplateQuestion $question, array $values): SurveyLink
    {
        $link = $this->link();
        $link->update(['completed_at' => now()]);
        $response = SurveyResponse::create(['survey_link_id' => $link->id, 'submitted_at' => now()]);
        SurveyAnswer::create(array_merge([
            'survey_response_id' => $response->id,
            'survey_template_question_id' => $question->id,
        ], $values));

        return $link;
    }

    private function save(array $questions)
    {
        return $this->actingAs($this->admin)
            ->put(route('admin.surveys.templates.update', $this->template), [
                'name' => 'Teamer-Feedback',
                'target_group' => 'teamer',
                'active' => true,
                'questions' => $questions,
            ]);
    }

    public function test_vorlage_mit_antworten_laesst_sich_speichern_und_id_bleibt(): void
    {
        $question = $this->question('Wie war das Essn?');
        $this->answer($question, ['rating_answer' => 8]);

        $this->save([
            ['id' => $question->id, 'question_text' => 'Wie war das Essen?', 'type' => 'rating', 'required' => '1'],
            ['question_text' => 'Neue Frage', 'type' => 'text'],
        ])->assertRedirect(route('admin.surveys.templates.index'));

        $question->refresh();
        $this->assertSame('Wie war das Essen?', $question->question_text);
        $this->assertTrue($question->required);
        $this->assertNull($question->archived_at);
        $this->assertSame(1, $question->answers()->count());
        $this->assertSame(
            ['Wie war das Essen?', 'Neue Frage'],
            $this->template->questions()->pluck('question_text')->all()
        );
    }

    public function test_reihenfolge_wird_aktualisiert(): void
    {
        $a = $this->question('A', 'text', 0);
        $b = $this->question('B', 'text', 1);

        $this->save([
            ['id' => $b->id, 'question_text' => 'B', 'type' => 'text'],
            ['id' => $a->id, 'question_text' => 'A', 'type' => 'text'],
        ])->assertRedirect();

        $this->assertSame([$b->id, $a->id], $this->template->questions()->pluck('id')->all());
    }

    public function test_typwechsel_mit_antworten_archiviert_alte_frage(): void
    {
        $question = $this->question('Wie viel Spaß hattest du?');
        $this->answer($question, ['rating_answer' => 9]);

        $this->save([
            ['id' => $question->id, 'question_text' => 'Wie viel Spaß hattest du?', 'type' => 'smiley'],
        ])->assertRedirect();

        $this->assertNotNull($question->fresh()->archived_at);
        $this->assertSame('rating', $question->fresh()->type);

        $active = $this->template->questions()->get();
        $this->assertCount(1, $active);
        $this->assertSame('smiley', $active->first()->type);
        $this->assertNotSame($question->id, $active->first()->id);
    }

    public function test_typwechsel_ohne_antworten_aktualisiert_die_frage(): void
    {
        $question = $this->question('Frage');

        $this->save([['id' => $question->id, 'question_text' => 'Frage', 'type' => 'yes_no']])->assertRedirect();

        $this->assertSame('yes_no', $question->fresh()->type);
        $this->assertSame(1, SurveyTemplateQuestion::count());
    }

    public function test_entfernen_archiviert_beantwortete_und_loescht_unbeantwortete_fragen(): void
    {
        $answered = $this->question('Beantwortet', 'text', 0);
        $unanswered = $this->question('Unbeantwortet', 'text', 1);
        $this->answer($answered, ['text_answer' => 'Toll!']);

        $this->save([])->assertRedirect(route('admin.surveys.templates.index'));

        $this->assertNotNull($answered->fresh()->archived_at);
        $this->assertNull($unanswered->fresh());
        $this->assertSame(0, $this->template->questions()->count());
        $this->assertSame(1, SurveyAnswer::count());
    }

    public function test_fremde_frage_id_legt_neue_frage_an(): void
    {
        $other = SurveyTemplate::create([
            'name' => 'Andere Vorlage',
            'target_group' => 'teamer',
            'active' => true,
            'created_by' => $this->admin->id,
        ]);
        $foreign = SurveyTemplateQuestion::create([
            'survey_template_id' => $other->id,
            'question_text' => 'Fremd',
            'type' => 'text',
            'sort_order' => 0,
        ]);

        $this->save([['id' => $foreign->id, 'question_text' => 'Übernommen?', 'type' => 'text']])->assertRedirect();

        $this->assertSame('Fremd', $foreign->fresh()->question_text);
        $this->assertSame($other->id, $foreign->fresh()->survey_template_id);
        $this->assertSame(['Übernommen?'], $this->template->questions()->pluck('question_text')->all());
    }

    public function test_editor_schickt_ids_mit_und_zeigt_antwort_hinweis(): void
    {
        $question = $this->question('Wie war das Event?');
        $this->answer($question, ['rating_answer' => 7]);

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.templates.edit', $this->template))
            ->assertOk()
            ->assertSee('name="questions[0][id]" value="'.$question->id.'"', false)
            ->assertSee('1 Antwort vorhanden');
    }

    public function test_archivierte_frage_fehlt_im_formular_bleibt_aber_in_der_auswertung(): void
    {
        $question = $this->question('Alte Frage', 'text');
        $answeredLink = $this->answer($question, ['text_answer' => 'Antwort auf die alte Frage']);
        $this->save([['question_text' => 'Neue Frage', 'type' => 'text']])->assertRedirect();

        $this->get(route('survey.show', $this->link()->token))
            ->assertOk()
            ->assertSee('Neue Frage')
            ->assertDontSee('Alte Frage');

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.results', $this->survey))
            ->assertOk()
            ->assertSee('Alte Frage')
            ->assertSee('entfernte Frage')
            ->assertSee('Antwort auf die alte Frage');

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.links.response', [$this->survey, $answeredLink]))
            ->assertOk()
            ->assertSee('Alte Frage')
            ->assertSee('entfernte Frage');

        $this->actingAs($this->admin)
            ->get(route('admin.surveys.export.pdf', $this->survey))
            ->assertOk();
    }

    public function test_offenes_formular_kann_nach_textaenderung_abgeschickt_werden(): void
    {
        $question = $this->question('Wie war das Essn?');
        $link = $this->link();

        // Formular ist offen, währenddessen wird der Fragetext korrigiert.
        $this->save([['id' => $question->id, 'question_text' => 'Wie war das Essen?', 'type' => 'rating']]);

        $this->post(route('survey.submit', $link->token), ['answers' => [$question->id => '9']])
            ->assertRedirect(route('survey.danke', $link->token));

        $this->assertSame(9, (int) $question->answers()->value('rating_answer'));
    }
}
