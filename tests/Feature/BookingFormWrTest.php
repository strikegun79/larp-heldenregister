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
use Tests\TestCase;

/**
 * Abenteuer-Anmeldung im Waldritter-Design: Abschnitte, verknüpfte Beschriftungen,
 * NSC korrekt erklärt, Fotoerlaubnis mit Widerrufshinweis, Skript-Haken erhalten.
 */
class BookingFormWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function form()
    {
        $user = User::factory()->create();
        $user->roles()->attach(60);
        $user->players()->attach(Player::factory()->create()->id, ['self' => true]);
        $adventure = Adventure::factory()->create([
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
            'start_at' => '2026-03-14 10:00',
            'end_at' => '2026-03-15 15:00',
        ]);

        return $this->actingAs($user)->get(route('adventures.bookings.create', $adventure), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_form_is_structured_in_sections_with_linked_labels(): void
    {
        $this->form()
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSeeInOrder(['Wer nimmt teil?', 'Gut zu wissen', 'Fotoerlaubnis', 'Gesundheit und Notfall', 'Zustimmung'])
            ->assertSee('<label for="booking-player-select">Spieler</label>', false)
            ->assertSee('<label for="booking-role-select">Rolle</label>', false)
            ->assertSee('Sa, 14.03., 10:00 Uhr – So, 15.03.2026, 15:00 Uhr');
    }

    public function test_nsc_is_explained_correctly_and_visibly(): void
    {
        $this->form()
            ->assertOk()
            ->assertSee('NSC (Nichtspielercharakter)')
            ->assertDontSee('Non-Spieler-Charakter');
    }

    public function test_photo_consent_explains_revocation(): void
    {
        $this->form()
            ->assertOk()
            ->assertSee('name="fotoerlaubnis"', false)
            ->assertSee('jederzeit im Spielerprofil unter „Fotoerlaubnis“ widerrufen');
    }

    public function test_script_hooks_stay_in_place(): void
    {
        $this->form()
            ->assertOk()
            ->assertSee('id="booking-create-form"', false)
            ->assertSee('id="health-consent-field-create"', false)
            ->assertSee('data-hcr-badge', false)
            ->assertSee('data-prefill-url', false)
            ->assertSee('form="booking-create-form"', false)
            ->assertSee('Anmeldung absenden');
    }

    public function test_guest_form_uses_sections_and_linked_labels(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(60);
        $adventure = Adventure::factory()->create(['event_status_id' => EventStatus::REGISTRATION_OPEN]);

        $this->actingAs($user)
            ->get(route('adventures.bookings.create-guest', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSee('keine Erfahrungspunkte')
            ->assertSeeInOrder(['Wer nimmt teil?', 'Gut zu wissen', 'Gesundheit und Notfall', 'Zustimmung'])
            ->assertSee('<label for="guest-name">Vorname</label>', false)
            ->assertSee('form="booking-guest-form"', false);
    }

    public function test_edit_form_keeps_values_hooks_and_explains_nsc(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);
        $adventure = Adventure::factory()->create();
        $booking = Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'vegetarier' => true,
            'allergien' => 'Nüsse',
        ]);

        $this->actingAs($admin)
            ->get(route('adventures.bookings.edit', [$adventure, $booking]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('id="booking-edit-form"', false)
            ->assertSee('id="health-consent-field-edit"', false)
            ->assertSee('<label for="edit-role">Rolle</label>', false)
            ->assertSee('NSC (Nichtspielercharakter)')
            ->assertDontSee('Non-Spieler-Charakter')
            ->assertSee('Nüsse')
            ->assertSeeInOrder(['name="vegetarier"', 'checked'], false);
    }
}
