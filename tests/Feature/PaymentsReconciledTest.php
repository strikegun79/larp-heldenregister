<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Issue #5: Datum des letzten Bezahlungsabgleichs pro Veranstaltung – setzen in der
 * Verwaltung (manage-payments), Anzeige für Teilnehmer bei offenem Beitrag.
 */
class PaymentsReconciledTest extends TestCase
{
    use RefreshDatabase;

    private const HINT = 'Zahlungseingänge zuletzt geprüft am 20.03.2026.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
        Carbon::setTestNow('2026-03-21 09:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat

        return $user;
    }

    private function adventure(array $attributes = []): Adventure
    {
        return Adventure::factory()->create(array_merge([
            'start_at' => '2026-04-14 10:00',
            'end_at' => '2026-04-15 15:00',
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
            'max_player' => 10,
            'fee' => 40,
        ], $attributes));
    }

    /** Nutzer mit eigenem Spieler und einer bestätigten Anmeldung. */
    private function participant(Adventure $adventure, bool $paid): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(60); // Event buchen
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);
        Booking::factory()->for($adventure)->create([
            'player_id' => $player->id, 'status' => 'bestaetigt', 'paid' => $paid, 'waitlisted' => false,
        ]);

        return $user;
    }

    public function test_registrar_sets_today_custom_date_and_clears_it(): void
    {
        $adventure = $this->adventure();
        $registrar = $this->registrar();
        $url = route('adventures.payments-reconciled', $adventure);

        $this->actingAs($registrar)->patchJson($url, ['today' => 1])
            ->assertOk()->assertJson(['message' => 'Letzter Abgleich: 21.03.2026.']);
        $this->assertSame('2026-03-21', $adventure->fresh()->payments_reconciled_at->toDateString());

        $this->actingAs($registrar)->patchJson($url, ['payments_reconciled_at' => '2026-03-18'])->assertOk();
        $this->assertSame('2026-03-18', $adventure->fresh()->payments_reconciled_at->toDateString());

        $this->actingAs($registrar)->patchJson($url, ['payments_reconciled_at' => ''])->assertOk();
        $this->assertNull($adventure->fresh()->payments_reconciled_at);
    }

    public function test_date_in_future_is_rejected(): void
    {
        $adventure = $this->adventure();

        $this->actingAs($this->registrar())
            ->patchJson(route('adventures.payments-reconciled', $adventure), ['payments_reconciled_at' => '2026-03-22'])
            ->assertStatus(422);
        $this->assertNull($adventure->fresh()->payments_reconciled_at);
    }

    public function test_only_payment_managers_may_set_the_date(): void
    {
        $adventure = $this->adventure();
        $user = User::factory()->create();
        $user->roles()->attach(60);

        $this->actingAs($user)
            ->patchJson(route('adventures.payments-reconciled', $adventure), ['today' => 1])
            ->assertForbidden();
        $this->assertNull($adventure->fresh()->payments_reconciled_at);
    }

    public function test_participant_with_open_fee_sees_hint_in_detail_and_dashboard(): void
    {
        $adventure = $this->adventure(['payments_reconciled_at' => '2026-03-20']);
        $user = $this->participant($adventure, paid: false);

        $this->actingAs($user)->get(route('adventures.show', $adventure))
            ->assertOk()
            ->assertSee('Zahlungseingänge geprüft bis 20.03.2026')
            ->assertSee(self::HINT);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(self::HINT);
    }

    public function test_no_hint_when_paid_without_date_or_free(): void
    {
        $paid = $this->adventure(['payments_reconciled_at' => '2026-03-20']);
        $this->actingAs($this->participant($paid, paid: true))->get(route('adventures.show', $paid))
            ->assertOk()->assertDontSee(self::HINT);

        $noDate = $this->adventure();
        $this->actingAs($this->participant($noDate, paid: false))->get(route('adventures.show', $noDate))
            ->assertOk()->assertDontSee('zuletzt geprüft')->assertDontSee('geprüft bis');

        $free = $this->adventure(['fee' => 0, 'payments_reconciled_at' => '2026-03-20']);
        $this->actingAs($this->participant($free, paid: false))->get(route('adventures.show', $free))
            ->assertOk()->assertDontSee('zuletzt geprüft')->assertDontSee('geprüft bis');
    }

    public function test_manage_view_shows_date_and_controls_for_registrar(): void
    {
        $adventure = $this->adventure(['payments_reconciled_at' => '2026-03-20']);
        $this->participant($adventure, paid: false);
        $admin = User::factory()->create();
        $admin->roles()->attach(10); // Admin (Verwaltung + Zahlungen)

        $this->actingAs($admin)->get(route('adventures.manage', $adventure))
            ->assertOk()
            ->assertSee('Letzter Abgleich der Zahlungen:')
            ->assertSee('<strong>20.03.2026</strong>', false)
            ->assertSee('Heute abgeglichen')
            ->assertSee(route('adventures.payments-reconciled', $adventure), false);
    }
}
