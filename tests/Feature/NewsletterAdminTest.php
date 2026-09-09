<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NL-ADMIN: Admin-CRUD für Newsletter (index, create, store, show, edit, update, destroy, duplicate).
 */
class NewsletterAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin

        return $user;
    }

    private function projektleitung(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(30); // Projektleitung hat newsletter.manage

        return $user;
    }

    private function ohneRecht(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat hat kein newsletter.manage

        return $user;
    }

    private function entwurf(array $attrs = []): Newsletter
    {
        return Newsletter::create(array_merge([
            'title'    => 'Test-Newsletter',
            'body_html' => '<p>Inhalt</p>',
            'status'   => Newsletter::STATUS_DRAFT,
        ], $attrs));
    }

    private function versendet(array $attrs = []): Newsletter
    {
        return Newsletter::create(array_merge([
            'title'    => 'Versendeter Newsletter',
            'body_html' => '<p>Inhalt</p>',
            'status'   => Newsletter::STATUS_SENT,
            'sent_at'  => now(),
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Zugriffskontrolle
    // -------------------------------------------------------------------------

    public function test_ohne_berechtigung_ist_index_verboten(): void
    {
        $this->actingAs($this->ohneRecht())
            ->get(route('admin.newsletter.index'))
            ->assertForbidden();
    }

    public function test_admin_kann_newsletter_verwalten(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.newsletter.index'))
            ->assertOk();
    }

    public function test_projektleitung_kann_newsletter_verwalten(): void
    {
        $this->actingAs($this->projektleitung())
            ->get(route('admin.newsletter.index'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_listet_newsletter(): void
    {
        $this->entwurf(['title' => 'Sommercamp-Update']);

        $this->actingAs($this->admin())
            ->get(route('admin.newsletter.index'))
            ->assertOk()
            ->assertSee('Sommercamp-Update');
    }

    // -------------------------------------------------------------------------
    // create / store
    // -------------------------------------------------------------------------

    public function test_create_zeigt_formular_mit_abonnenten_anzahl(): void
    {
        NewsletterSubscription::create([
            'email'        => 'a@example.test',
            'confirmed_at' => now(),
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.newsletter.create'))
            ->assertOk()
            ->assertViewHas('activeSubscribers', 1);
    }

    public function test_store_legt_entwurf_an_und_leitet_zu_bearbeiten_weiter(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.store'), [
                'title'    => 'Herbstlager Einladung',
                'body_html' => '<p>Hallo!</p>',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletters', [
            'title'  => 'Herbstlager Einladung',
            'status' => Newsletter::STATUS_DRAFT,
        ]);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_zeigt_newsletter_readonly(): void
    {
        $newsletter = $this->versendet(['title' => 'Winterfest-Info']);

        $this->actingAs($this->admin())
            ->get(route('admin.newsletter.show', $newsletter))
            ->assertOk()
            ->assertSee('Winterfest-Info');
    }

    // -------------------------------------------------------------------------
    // edit / update
    // -------------------------------------------------------------------------

    public function test_edit_zeigt_formular_fuer_entwurf(): void
    {
        $newsletter = $this->entwurf(['title' => 'Entwurf XY']);

        $this->actingAs($this->admin())
            ->get(route('admin.newsletter.edit', $newsletter))
            ->assertOk()
            ->assertSee('Entwurf XY');
    }

    public function test_edit_auf_versendetem_newsletter_ist_verboten(): void
    {
        $newsletter = $this->versendet();

        $this->actingAs($this->admin())
            ->get(route('admin.newsletter.edit', $newsletter))
            ->assertForbidden();
    }

    public function test_update_speichert_aenderungen(): void
    {
        $newsletter = $this->entwurf(['title' => 'Alt']);

        $this->actingAs($this->admin())
            ->patch(route('admin.newsletter.update', $newsletter), [
                'title'    => 'Neu',
                'body_html' => '<p>Neuer Inhalt</p>',
            ])
            ->assertRedirect(route('admin.newsletter.edit', $newsletter));

        $this->assertDatabaseHas('newsletters', ['id' => $newsletter->id, 'title' => 'Neu']);
    }

    public function test_update_via_xhr_gibt_json_zurueck(): void
    {
        $newsletter = $this->entwurf();

        $this->actingAs($this->admin())
            ->patchJson(route('admin.newsletter.update', $newsletter), [
                'title'    => 'Auto-Save Titel',
                'body_html' => '<p>Inhalt</p>',
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Gespeichert.']);
    }

    public function test_update_auf_versendetem_newsletter_ist_verboten(): void
    {
        $newsletter = $this->versendet();

        $this->actingAs($this->admin())
            ->patch(route('admin.newsletter.update', $newsletter), [
                'title'    => 'Hack',
                'body_html' => '<p>x</p>',
            ])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_loescht_entwurf(): void
    {
        $newsletter = $this->entwurf();

        $this->actingAs($this->admin())
            ->delete(route('admin.newsletter.destroy', $newsletter))
            ->assertRedirect(route('admin.newsletter.index'));

        $this->assertSoftDeleted('newsletters', ['id' => $newsletter->id]);
    }

    public function test_destroy_loescht_auch_versendeten_newsletter(): void
    {
        $newsletter = $this->versendet();

        $this->actingAs($this->admin())
            ->delete(route('admin.newsletter.destroy', $newsletter))
            ->assertRedirect(route('admin.newsletter.index'));

        $this->assertSoftDeleted('newsletters', ['id' => $newsletter->id]);
    }

    // -------------------------------------------------------------------------
    // duplicate
    // -------------------------------------------------------------------------

    public function test_duplicate_erstellt_entwurfs_kopie(): void
    {
        $original = $this->versendet(['title' => 'Original', 'body_html' => '<p>Text</p>']);

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.duplicate', $original))
            ->assertRedirect();

        $this->assertDatabaseHas('newsletters', [
            'title'  => 'Kopie: Original',
            'status' => Newsletter::STATUS_DRAFT,
        ]);
    }
}
