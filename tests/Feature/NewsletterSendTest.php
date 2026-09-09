<?php

namespace Tests\Feature;

use App\Jobs\SendNewsletterJob;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * NL-SEND: Newsletter-Versand – Controller-Action und SendNewsletterJob.
 */
class NewsletterSendTest extends TestCase
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
        $user->roles()->attach(10);

        return $user;
    }

    private function entwurf(): Newsletter
    {
        return Newsletter::create([
            'title'    => 'Test-Versand',
            'body_html' => '<p>Hallo Welt</p>',
            'status'   => Newsletter::STATUS_DRAFT,
        ]);
    }

    private function aktivesAbo(string $email = 'empf@example.test'): NewsletterSubscription
    {
        return NewsletterSubscription::create([
            'email'        => $email,
            'confirmed_at' => now(),
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);
    }

    // -------------------------------------------------------------------------
    // Controller: send-Action
    // -------------------------------------------------------------------------

    public function test_versenden_ohne_bestaetigung_schlaegt_fehl(): void
    {
        $newsletter = $this->entwurf();
        $this->aktivesAbo();

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.send', $newsletter), [
                'confirm_word' => '',
            ])
            ->assertSessionHasErrors('confirm_word');
    }

    public function test_versenden_mit_falschem_bestaetigung_schlaegt_fehl(): void
    {
        $newsletter = $this->entwurf();
        $this->aktivesAbo();

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.send', $newsletter), [
                'confirm_word' => 'SENDEN',
            ])
            ->assertSessionHasErrors('confirm_word');
    }

    public function test_versenden_auf_bereits_versendetem_newsletter_ist_verboten(): void
    {
        $newsletter = Newsletter::create([
            'title'    => 'Schon versendet',
            'body_html' => '<p>x</p>',
            'status'   => Newsletter::STATUS_SENT,
            'sent_at'  => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.send', $newsletter), [
                'confirm_word' => 'VERSENDEN',
            ])
            ->assertForbidden();
    }

    public function test_versenden_ohne_aktive_abonnenten_gibt_fehler_zurueck(): void
    {
        Bus::fake();
        $newsletter = $this->entwurf();

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.send', $newsletter), [
                'confirm_word' => 'VERSENDEN',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Bus::assertNotDispatched(SendNewsletterJob::class);
    }

    public function test_versenden_setzt_status_sofort_auf_sent_und_dispatcht_job(): void
    {
        Bus::fake();
        $newsletter = $this->entwurf();
        $this->aktivesAbo();

        $this->actingAs($this->admin())
            ->post(route('admin.newsletter.send', $newsletter), [
                'confirm_word' => 'VERSENDEN',
            ])
            ->assertRedirect(route('admin.newsletter.index'))
            ->assertSessionHas('success');

        // Status muss sofort auf 'sent' stehen (nicht erst nach Job-Verarbeitung)
        $this->assertDatabaseHas('newsletters', [
            'id'     => $newsletter->id,
            'status' => Newsletter::STATUS_SENT,
        ]);

        Bus::assertDispatched(SendNewsletterJob::class);
    }

    // -------------------------------------------------------------------------
    // SendNewsletterJob
    // -------------------------------------------------------------------------

    public function test_job_sendet_mail_an_aktive_abonnenten(): void
    {
        Mail::fake();
        $newsletter = $this->entwurf();
        $this->aktivesAbo('a@example.test');
        $this->aktivesAbo('b@example.test');

        (new SendNewsletterJob($newsletter))->handle();

        Mail::assertQueued(NewsletterMail::class, 2);
    }

    public function test_job_ueberspringt_nicht_bestaetigte_abonnenten(): void
    {
        Mail::fake();
        $newsletter = $this->entwurf();

        // Unbestätigt (confirmed_at = null)
        NewsletterSubscription::create([
            'email'        => 'unbestaetigt@example.test',
            'confirmed_at' => null,
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);

        (new SendNewsletterJob($newsletter))->handle();

        Mail::assertNothingQueued();
    }

    public function test_job_ueberspringt_abgemeldete_abonnenten(): void
    {
        Mail::fake();
        $newsletter = $this->entwurf();

        NewsletterSubscription::create([
            'email'           => 'abgemeldet@example.test',
            'confirmed_at'    => now()->subDay(),
            'consented_at'    => now()->subDay(),
            'consent_text'    => 'Test',
            'unsubscribed_at' => now()->subHour(),
        ]);

        (new SendNewsletterJob($newsletter))->handle();

        Mail::assertNothingQueued();
    }

    public function test_job_verhindert_doppelten_versand(): void
    {
        Mail::fake();
        $newsletter = $this->entwurf();
        $abo = $this->aktivesAbo();

        // Simuliere bereits vorhandene Send-Einträge (erster Versand war erfolgreich)
        NewsletterSend::create([
            'newsletter_id'   => $newsletter->id,
            'subscription_id' => $abo->id,
        ]);

        // Job ein zweites Mal ausführen → darf nichts mehr senden
        (new SendNewsletterJob($newsletter))->handle();

        Mail::assertNothingQueued();
    }

    public function test_job_erstellt_send_eintraege_pro_empfaenger(): void
    {
        Mail::fake();
        $newsletter = $this->entwurf();
        $this->aktivesAbo('a@example.test');
        $this->aktivesAbo('b@example.test');

        (new SendNewsletterJob($newsletter))->handle();

        $this->assertDatabaseCount('newsletter_sends', 2);
    }
}
