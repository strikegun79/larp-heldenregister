<?php

namespace Tests\Feature;

use App\Mail\SurveyInvitationMail;
use App\Models\Adventure;
use App\Models\Survey;
use App\Models\SurveyLink;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * SURV-ADMIN: Umfragen-Verwaltung – Zugriffskontrolle, CRUD, Einladungen, Ergebnisse.
 */
class SurveyAdminTest extends TestCase
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

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin → survey.admin + survey.view

        return $user;
    }

    private function projektleitung(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(30); // survey.admin + survey.view

        return $user;
    }

    private function ohneRecht(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat – keine Survey-Rechte

        return $user;
    }

    private function template(string $targetGroup = 'teamer'): SurveyTemplate
    {
        return SurveyTemplate::create([
            'name'         => 'Testvorlage',
            'target_group' => $targetGroup,
            'active'       => true,
            'created_by'   => $this->creator->id,
        ]);
    }

    private function question(SurveyTemplate $template, string $type = 'rating'): SurveyTemplateQuestion
    {
        return SurveyTemplateQuestion::create([
            'survey_template_id' => $template->id,
            'question_text'      => 'Wie war das Event?',
            'type'               => $type,
            'sort_order'         => 0,
            'required'           => true,
        ]);
    }

    private function survey(SurveyTemplate $template, string $status = 'active'): Survey
    {
        $adventure = Adventure::factory()->create();

        return Survey::create([
            'adventure_id'       => $adventure->id,
            'survey_template_id' => $template->id,
            'title'              => 'Test-Umfrage',
            'status'             => $status,
            'created_by'         => $this->creator->id,
        ]);
    }

    private function link(Survey $survey, array $attrs = []): SurveyLink
    {
        return SurveyLink::create(array_merge([
            'survey_id'   => $survey->id,
            'target_type' => 'teamer',
            'name'        => 'Testteilnehmer',
            'email'       => 'teilnehmer@example.test',
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Zugriffskontrolle
    // -------------------------------------------------------------------------

    public function test_ohne_berechtigung_ist_index_verboten(): void
    {
        $this->actingAs($this->ohneRecht())
            ->get(route('admin.surveys.index'))
            ->assertForbidden();
    }

    public function test_projektleitung_kann_umfragen_einsehen(): void
    {
        $this->actingAs($this->projektleitung())
            ->get(route('admin.surveys.index'))
            ->assertOk();
    }

    public function test_projektleitung_kann_umfrage_erstellen(): void
    {
        $this->actingAs($this->projektleitung())
            ->get(route('admin.surveys.create'))
            ->assertOk();
    }

    public function test_admin_kann_umfrage_erstellen(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.surveys.create'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_legt_umfrage_als_entwurf_an(): void
    {
        $tmpl      = $this->template();
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.surveys.store'), [
                'adventure_id'       => $adventure->id,
                'survey_template_id' => $tmpl->id,
                'title'              => 'Sommerlager-Feedback',
            ])
            ->assertRedirect(route('admin.surveys.index'));

        $this->assertDatabaseHas('surveys', [
            'title'  => 'Sommerlager-Feedback',
            'status' => 'draft',
        ]);
    }

    // -------------------------------------------------------------------------
    // results / showResponse
    // -------------------------------------------------------------------------

    public function test_projektleitung_kann_ergebnisse_einsehen(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);

        $this->actingAs($this->projektleitung())
            ->get(route('admin.surveys.results', $survey))
            ->assertOk();
    }

    public function test_ohne_berechtigung_ist_ergebnisse_verboten(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);

        $this->actingAs($this->ohneRecht())
            ->get(route('admin.surveys.results', $survey))
            ->assertForbidden();
    }

    public function test_show_response_zeigt_einzelantwort(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, ['completed_at' => now()]);

        SurveyResponse::create([
            'survey_link_id' => $link->id,
            'submitted_at'   => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.surveys.links.response', [$survey, $link]))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // send (manuelle Einladungsliste)
    // -------------------------------------------------------------------------

    public function test_send_erstellt_links_und_queued_einladungsmails(): void
    {
        Mail::fake();
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl, 'draft');

        $this->actingAs($this->admin())
            ->post(route('admin.surveys.send', $survey), [
                'recipients' => [
                    [
                        'name'        => 'Anna Waldläuferin',
                        'email'       => 'anna@example.test',
                        'target_type' => 'teamer',
                        'player_id'   => null,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('survey_links', ['email' => 'anna@example.test']);
        $this->assertDatabaseHas('surveys', ['id' => $survey->id, 'status' => 'active']);

        Mail::assertQueued(SurveyInvitationMail::class, 1);
    }

    public function test_send_auf_geschlossener_umfrage_gibt_fehler(): void
    {
        Mail::fake();
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl, 'closed');

        $this->actingAs($this->admin())
            ->post(route('admin.surveys.send', $survey), [
                'recipients' => [[
                    'name' => 'X', 'email' => 'x@example.test',
                    'target_type' => 'teamer', 'player_id' => null,
                ]],
            ])
            ->assertSessionHas('error');

        Mail::assertNothingQueued();
    }

    // -------------------------------------------------------------------------
    // sendLink (einzelner Link erneut versenden)
    // -------------------------------------------------------------------------

    public function test_send_link_queued_einladungsmail_und_setzt_sent_at(): void
    {
        Mail::fake();
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey);

        $this->actingAs($this->admin())
            ->post(route('admin.surveys.links.send', [$survey, $link]))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(SurveyInvitationMail::class, fn ($m) => $m->hasTo($link->email));
        $this->assertNotNull($link->fresh()->sent_at);
    }

    public function test_send_link_bei_abgeschlossenem_link_gibt_fehler(): void
    {
        Mail::fake();
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, ['completed_at' => now()]);

        $this->actingAs($this->admin())
            ->post(route('admin.surveys.links.send', [$survey, $link]))
            ->assertSessionHas('error');

        Mail::assertNothingQueued();
    }

    // -------------------------------------------------------------------------
    // destroyLink
    // -------------------------------------------------------------------------

    public function test_destroy_link_loescht_nicht_beantworteten_link(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey);

        $this->actingAs($this->admin())
            ->delete(route('admin.surveys.links.destroy', [$survey, $link]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('survey_links', ['id' => $link->id]);
    }

    public function test_destroy_link_bei_beantwortetem_link_gibt_fehler(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);
        $link   = $this->link($survey, ['completed_at' => now()]);

        $this->actingAs($this->admin())
            ->delete(route('admin.surveys.links.destroy', [$survey, $link]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('survey_links', ['id' => $link->id]);
    }

    // -------------------------------------------------------------------------
    // close
    // -------------------------------------------------------------------------

    public function test_close_setzt_status_auf_closed(): void
    {
        $tmpl   = $this->template();
        $survey = $this->survey($tmpl);

        $this->actingAs($this->admin())
            ->patch(route('admin.surveys.close', $survey))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('surveys', ['id' => $survey->id, 'status' => 'closed']);
    }
}
