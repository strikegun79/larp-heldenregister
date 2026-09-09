<?php

namespace Tests\Feature;

use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * NL-SUB: Double-Opt-in-Abonnement-Flow (subscribe, confirm, unsubscribe).
 */
class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class]);
    }

    private function user(string $email = 'nutzer@example.test'): User
    {
        return User::factory()->create(['email' => $email]);
    }

    // -------------------------------------------------------------------------
    // subscribe
    // -------------------------------------------------------------------------

    public function test_subscribe_legt_abo_an_und_sendet_bestaetigungsmail(): void
    {
        Mail::fake();
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('newsletter.subscribe'))
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'confirmation_sent');

        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email'        => $user->email,
            'confirmed_at' => null,
        ]);

        Mail::assertQueued(NewsletterConfirmationMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_subscribe_bei_aktivem_abo_gibt_already_subscribed_zurueck(): void
    {
        Mail::fake();
        $user = $this->user();

        NewsletterSubscription::create([
            'user_id'      => $user->id,
            'email'        => $user->email,
            'confirmed_at' => now(),
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);

        $this->actingAs($user)
            ->post(route('newsletter.subscribe'))
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'already_subscribed');

        Mail::assertNothingQueued();
    }

    public function test_subscribe_nach_abmeldung_reaktiviert_abo_und_sendet_mail(): void
    {
        Mail::fake();
        $user = $this->user();

        NewsletterSubscription::create([
            'user_id'         => $user->id,
            'email'           => $user->email,
            'confirmed_at'    => now()->subDay(),
            'consented_at'    => now()->subDay(),
            'consent_text'    => 'Alt',
            'unsubscribed_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->post(route('newsletter.subscribe'))
            ->assertSessionHas('newsletter_status', 'confirmation_sent');

        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email'           => $user->email,
            'unsubscribed_at' => null,
            'confirmed_at'    => null,
        ]);

        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_subscribe_bei_noch_nicht_bestaetigt_sendet_neue_bestaetigungsmail(): void
    {
        Mail::fake();
        $user = $this->user();

        NewsletterSubscription::create([
            'user_id'      => $user->id,
            'email'        => $user->email,
            'confirmed_at' => null,
            'consented_at' => now()->subMinutes(5),
            'consent_text' => 'Alt',
        ]);

        $this->actingAs($user)
            ->post(route('newsletter.subscribe'))
            ->assertSessionHas('newsletter_status', 'confirmation_sent');

        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    // -------------------------------------------------------------------------
    // unsubscribe
    // -------------------------------------------------------------------------

    public function test_unsubscribe_setzt_unsubscribed_at(): void
    {
        $user = $this->user();

        NewsletterSubscription::create([
            'user_id'      => $user->id,
            'email'        => $user->email,
            'confirmed_at' => now(),
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);

        $this->actingAs($user)
            ->post(route('newsletter.unsubscribe'))
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'unsubscribed');

        $this->assertDatabaseMissing('newsletter_subscriptions', [
            'email'           => $user->email,
            'unsubscribed_at' => null,
        ]);
    }

    public function test_unsubscribe_ohne_aktives_abo_ist_problemlos(): void
    {
        $this->actingAs($this->user())
            ->post(route('newsletter.unsubscribe'))
            ->assertRedirect()
            ->assertSessionHas('newsletter_status', 'unsubscribed');
    }

    // -------------------------------------------------------------------------
    // confirm (Double-Opt-in, öffentlich)
    // -------------------------------------------------------------------------

    public function test_confirm_mit_gueltigem_token_bestaetigt_abo(): void
    {
        $sub = NewsletterSubscription::create([
            'email'        => 'sub@example.test',
            'confirmed_at' => null,
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);

        $this->get(route('newsletter.confirm', $sub->token))
            ->assertOk()
            ->assertViewIs('newsletter.confirmed');

        $this->assertNotNull($sub->fresh()->confirmed_at);
    }

    public function test_confirm_mit_ungueltigem_token_zeigt_fehlerseite(): void
    {
        $this->get(route('newsletter.confirm', 'ungueltig-xyz'))
            ->assertOk()
            ->assertViewIs('newsletter.confirm-invalid');
    }

    public function test_confirm_bei_abgemeldetem_abo_zeigt_fehlerseite(): void
    {
        $sub = NewsletterSubscription::create([
            'email'           => 'sub@example.test',
            'confirmed_at'    => now()->subDay(),
            'consented_at'    => now()->subDay(),
            'consent_text'    => 'Test',
            'unsubscribed_at' => now()->subHour(),
        ]);

        $this->get(route('newsletter.confirm', $sub->token))
            ->assertOk()
            ->assertViewIs('newsletter.confirm-invalid');
    }

    public function test_confirm_bei_bereits_bestaetigt_ist_idempotent(): void
    {
        $confirmedAt = now()->subDay();
        $sub = NewsletterSubscription::create([
            'email'        => 'sub@example.test',
            'confirmed_at' => $confirmedAt,
            'consented_at' => $confirmedAt,
            'consent_text' => 'Test',
        ]);

        $this->get(route('newsletter.confirm', $sub->token))
            ->assertOk()
            ->assertViewIs('newsletter.confirmed');

        // confirmed_at darf nicht überschrieben werden
        $this->assertEquals(
            $confirmedAt->toDateTimeString(),
            $sub->fresh()->confirmed_at->toDateTimeString()
        );
    }

    // -------------------------------------------------------------------------
    // Dashboard-Banner
    // -------------------------------------------------------------------------

    public function test_dashboard_zeigt_newsletter_hinweis_wenn_kein_abo(): void
    {
        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('showNewsletterHint', true);
    }

    public function test_dashboard_versteckt_hinweis_bei_aktivem_abo(): void
    {
        $user = $this->user();

        NewsletterSubscription::create([
            'user_id'      => $user->id,
            'email'        => $user->email,
            'confirmed_at' => now(),
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertViewHas('showNewsletterHint', false);
    }

    public function test_dashboard_versteckt_hinweis_bei_noch_nicht_bestaetigt(): void
    {
        $user = $this->user();

        NewsletterSubscription::create([
            'user_id'      => $user->id,
            'email'        => $user->email,
            'confirmed_at' => null,
            'consented_at' => now(),
            'consent_text' => 'Test',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertViewHas('showNewsletterHint', false);
    }
}
