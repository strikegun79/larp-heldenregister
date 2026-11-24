<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\EventRole;
use App\Models\EventStatus;
use App\Models\TeamerSignup;
use App\Models\User;
use App\Support\GermanNumberWords;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #19 (T3): Teamer laden ihre ausgefüllte Abrechnung herunter – nur die eigene,
 * nur bestätigt, mit abrechnungsfähiger Rolle, Pauschale und vollständigem Profil.
 */
class TeamerCompensationDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
        EventRole::findOrFail(4)->update(['billable' => true, 'default_compensation' => 63]);
    }

    private function teamer(bool $complete = true): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Erika', 'lastname' => 'Muster', 'email_verified_at' => now(),
            'street' => 'Waldweg', 'house_number' => '1', 'zip' => '35390', 'city' => 'Gießen',
        ], $complete ? [
            'teamer_tax_number' => '020/250/63440', 'teamer_account_holder' => 'Erika Muster',
            'teamer_iban' => 'DE89370400440532013000', 'teamer_bank_name' => 'Volksbank',
        ] : []));
        $user->roles()->attach(50);

        return $user;
    }

    private function signupFor(User $user, array $attributes = [], ?string $amount = '63.00'): array
    {
        $adventure = Adventure::factory()->create([
            'name' => 'Sommerlager', 'kuerzel' => 'SoLa',
            'start_at' => '2026-07-10 10:00', 'end_at' => '2026-07-12 15:00',
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
        ]);
        if ($amount !== null) {
            $adventure->compensations()->create(['event_role_id' => 4, 'amount' => $amount]);
        }
        $signup = TeamerSignup::factory()->create(array_merge([
            'adventure_id' => $adventure->id, 'user_id' => $user->id,
            'event_role_id' => 4, 'approved_at' => now(),
        ], $attributes));

        return [$adventure, $signup];
    }

    public function test_teamer_downloads_own_pdf(): void
    {
        $user = $this->teamer();
        [$adventure, $signup] = $this->signupFor($user);

        $response = $this->actingAs($user)->get(route('adventures.teamer.compensation', [$adventure, $signup]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('Aufwandsentschaedigung_sola_muster_2026-07-10.pdf', $response->headers->get('content-disposition'));
    }

    public function test_pdf_view_contains_filled_values(): void
    {
        $user = $this->teamer();
        [$adventure, $signup] = $this->signupFor($user);
        $signup->load('eventRole');

        $html = view('adventures.compensation_pdf', [
            'adventure' => $adventure->load('location'), 'signup' => $signup, 'user' => $user,
            'amount' => '63.00', 'amountWords' => GermanNumberWords::euro('63.00'), 'iban' => 'DE89 3704 0044 0532 0130 00',
        ])->render();

        foreach (['als <strong>Teamer B</strong>', '63,00 €', 'dreiundsechzig Euro', 'Sommerlager', '10.07.2026 – 12.07.2026',
            'Muster, Erika', 'Waldweg 1', '35390 Gießen', '020/250/63440', 'DE89 3704 0044 0532 0130 00', 'Volksbank',
            'Aufwandsentschädigung: Sommerlager – 2026'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_other_users_cannot_download(): void
    {
        [$adventure, $signup] = $this->signupFor($this->teamer());
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        $this->actingAs($admin)->get(route('adventures.teamer.compensation', [$adventure, $signup]))->assertForbidden();
    }

    public function test_blocked_when_not_approved_without_amount_or_incomplete_profile(): void
    {
        $user = $this->teamer();
        [$adventure, $signup] = $this->signupFor($user, ['approved_at' => null]);
        $this->actingAs($user)->get(route('adventures.teamer.compensation', [$adventure, $signup]))
            ->assertRedirect()->assertSessionHas('error', 'Die Abrechnung ist möglich, sobald deine Teamer-Anmeldung bestätigt ist.');

        [$adventure, $signup] = $this->signupFor($user, [], null);
        $this->actingAs($user)->get(route('adventures.teamer.compensation', [$adventure, $signup]))
            ->assertRedirect()->assertSessionHas('error', 'Für deine Rolle ist bei dieser Veranstaltung keine Aufwandsentschädigung festgelegt.');

        $incomplete = $this->teamer(false);
        [$adventure, $signup] = $this->signupFor($incomplete);
        $this->actingAs($incomplete)->get(route('adventures.teamer.compensation', [$adventure, $signup]))
            ->assertRedirect()->assertSessionHas('error', 'Im Profil fehlt noch: Steuernummer, Kontoinhaber, IBAN, Name der Bank.');
    }

    public function test_detail_shows_card_with_download_or_reason(): void
    {
        $user = $this->teamer();
        [$adventure, $signup] = $this->signupFor($user);

        $this->actingAs($user)->get(route('adventures.show', $adventure))
            ->assertOk()
            ->assertSee('Deine Teamer-Anmeldung')
            ->assertSee('Aufwandsentschädigung 63,00 €')
            ->assertSee(route('adventures.teamer.compensation', [$adventure, $signup]), false)
            ->assertSee('Bitte ausdrucken und unterschreiben oder füge mit Acrobat deine Signatur hinzu.')
            ->assertSee('Deine Anmeldungen (1)');

        $incomplete = $this->teamer(false);
        [$adventure2] = $this->signupFor($incomplete);
        $this->actingAs($incomplete)->get(route('adventures.show', $adventure2))
            ->assertOk()
            ->assertSee('Für die Abrechnung fehlt in deinem Profil noch: Steuernummer, Kontoinhaber, IBAN, Name der Bank.')
            ->assertDontSee('Abrechnung herunterladen (PDF)');
    }

    public function test_dashboard_lists_billable_signup_with_download(): void
    {
        $user = $this->teamer();
        [$adventure, $signup] = $this->signupFor($user);
        $adventure->update(['start_at' => now()->addDays(10), 'end_at' => now()->addDays(12)]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Deine Teamer-Abrechnung')
            ->assertSee('Sommerlager')
            ->assertSee('Aufwandsentschädigung 63,00 €')
            ->assertSee(route('adventures.teamer.compensation', [$adventure, $signup]), false);
    }

    public function test_dashboard_points_to_profile_when_billing_data_missing(): void
    {
        $user = $this->teamer(false);
        // Vor 30 Tagen beendet: liegt noch im 60-Tage-Fenster
        [$adventure] = $this->signupFor($user);
        $adventure->update(['start_at' => now()->subDays(32), 'end_at' => now()->subDays(30)]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Deine Teamer-Abrechnung')
            ->assertSee('Für die Abrechnung fehlt in deinem Profil noch: Steuernummer, Kontoinhaber, IBAN, Name der Bank.')
            ->assertDontSee('Abrechnung herunterladen (PDF)');
    }

    public function test_dashboard_hides_unapproved_without_amount_old_or_cancelled(): void
    {
        $user = $this->teamer();
        $future = ['start_at' => now()->addDays(10), 'end_at' => now()->addDays(12)];

        [$unapproved] = $this->signupFor($user, ['approved_at' => null]);
        $unapproved->update($future);
        [$withoutAmount] = $this->signupFor($user, [], null);
        $withoutAmount->update($future);
        [$old] = $this->signupFor($user);
        $old->update(['start_at' => now()->subDays(70), 'end_at' => now()->subDays(61)]);
        [$cancelled] = $this->signupFor($user);
        $cancelled->update($future + ['event_status_id' => EventStatus::CANCELLED]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Deine Teamer-Abrechnung');
    }
}
