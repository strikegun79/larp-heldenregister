<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-19: Check-in über Unterschrift, Abmelde-Multimodal, Teilnehmerliste mit
 * Unterschrift-Spalte.
 */
class EventCheckinTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    private function bookingFor(Adventure $adventure): Booking
    {
        return Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
        ]);
    }

    public function test_saving_signature_also_checks_the_participant_in(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(30)) // Projektleitung (take-signatures)
            ->putJson(route('adventures.bookings.signature.update', [$adventure, $booking]), [
                'signature' => self::PNG,
            ])
            ->assertOk();

        $booking->refresh();
        $this->assertNotNull($booking->signature);
        // Unterschrift bestätigt zugleich den Check-in.
        $this->assertDatabaseHas('event_visits', [
            'adventure_id' => $adventure->id,
            'player_id' => $booking->player_id,
        ]);
    }

    public function test_checkin_tab_lists_signature_column_and_triggers(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        $response = $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->get(route('adventures.manage', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $response->assertSee('Unterschrift');
        $response->assertSee('checkin-trigger', false);
        $response->assertSee('deregister-trigger', false);
        // Die separate Unterschriften-Liste ist entfallen.
        $response->assertDontSee('Unterschriften &amp; Teilnehmerliste', false);
    }

    public function test_project_lead_can_deregister_via_manage_checkin(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        // Projektleitung hat kein manage-attendance, aber manage-checkin.
        $this->actingAs($this->userWithRole(30))
            ->patchJson(route('adventures.bookings.deregister', [$adventure, $booking]), [
                'absence_reason' => 'nicht_erschienen',
            ])
            ->assertOk();

        $this->assertSame('abgemeldet', $booking->fresh()->status);
    }

    public function test_pdf_is_served_inline(): void
    {
        $adventure = Adventure::factory()->create();
        $this->bookingFor($adventure);

        $response = $this->actingAs($this->userWithRole(30))
            ->get(route('adventures.participants-pdf', $adventure));

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }
}
