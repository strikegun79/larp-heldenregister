<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SURV-TMPL: Umfrage-Vorlagenverwaltung – nur survey.admin.
 */
class SurveyTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
        $this->creator = User::factory()->create();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10);

        return $user;
    }

    private function projektleitung(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(30); // survey.view, aber kein survey.admin

        return $user;
    }

    private function template(string $targetGroup = 'teamer'): SurveyTemplate
    {
        return SurveyTemplate::create([
            'name'         => 'Teamer-Vorlage',
            'target_group' => $targetGroup,
            'active'       => true,
            'created_by'   => $this->creator->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Zugriffskontrolle
    // -------------------------------------------------------------------------

    public function test_projektleitung_kann_vorlagen_nicht_verwalten(): void
    {
        $this->actingAs($this->projektleitung())
            ->get(route('admin.surveys.templates.index'))
            ->assertForbidden();
    }

    public function test_admin_kann_vorlagen_einsehen(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.surveys.templates.index'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_legt_vorlage_mit_fragen_an(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.surveys.templates.store'), [
                'name'         => 'Kinder-Fragebogen',
                'target_group' => 'participant_child',
                'active'       => true,
                'questions'    => [
                    ['question_text' => 'Hat dir das Event Spaß gemacht?', 'type' => 'yes_no',  'required' => true],
                    ['question_text' => 'Was hat dir besonders gut gefallen?', 'type' => 'text', 'required' => false],
                ],
            ])
            ->assertRedirect(route('admin.surveys.templates.index'));

        $this->assertDatabaseHas('survey_templates', ['name' => 'Kinder-Fragebogen']);
        $this->assertDatabaseCount('survey_template_questions', 2);
    }

    public function test_store_validiert_pflichtfelder(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.surveys.templates.store'), [
                'name'         => '',
                'target_group' => 'ungueltig',
            ])
            ->assertSessionHasErrors(['name', 'target_group']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_ersetzt_fragen_vollstaendig(): void
    {
        $template = $this->template();
        SurveyTemplateQuestion::create([
            'survey_template_id' => $template->id,
            'question_text'      => 'Alte Frage',
            'type'               => 'text',
            'sort_order'         => 0,
            'required'           => false,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.surveys.templates.update', $template), [
                'name'         => 'Aktualisierte Vorlage',
                'target_group' => 'teamer',
                'active'       => true,
                'questions'    => [
                    ['question_text' => 'Neue Frage', 'type' => 'rating', 'required' => true],
                ],
            ])
            ->assertRedirect(route('admin.surveys.templates.index'));

        $this->assertDatabaseHas('survey_templates', ['name' => 'Aktualisierte Vorlage']);
        $this->assertDatabaseMissing('survey_template_questions', ['question_text' => 'Alte Frage']);
        $this->assertDatabaseHas('survey_template_questions', ['question_text' => 'Neue Frage']);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_loescht_unbenutzte_vorlage(): void
    {
        $template = $this->template();

        $this->actingAs($this->admin())
            ->delete(route('admin.surveys.templates.destroy', $template))
            ->assertRedirect(route('admin.surveys.templates.index'));

        $this->assertDatabaseMissing('survey_templates', ['id' => $template->id]);
    }

    public function test_destroy_verhindert_loeschung_bei_vorhandenen_umfragen(): void
    {
        $template  = $this->template();
        $adventure = Adventure::factory()->create();

        Survey::create([
            'adventure_id'       => $adventure->id,
            'survey_template_id' => $template->id,
            'title'              => 'Aktive Umfrage',
            'status'             => 'active',
            'created_by'         => $this->creator->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.surveys.templates.destroy', $template))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('survey_templates', ['id' => $template->id]);
    }
}
