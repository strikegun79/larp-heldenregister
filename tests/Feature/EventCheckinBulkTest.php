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
 * Issue #11: Mehrfach-Check-in mit einer Unterschrift und Mehrfach-Abmeldung.
 */
class EventCheckinBulkTest extends TestCase
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

    private function bookingFor(Adventure $adventure, array $attributes = []): Booking
    {
        return Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            ...$attributes,
        ]);
    }

    public function test_bulk_checkin_stores_one_signature_for_all_selected(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $a = $this->bookingFor($adventure);
        $b = $this->bookingFor($adventure);
        $notSelected = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(30)) // Projektleitung
            ->putJson(route('adventures.checkin.bulk', $adventure), [
                'booking_ids' => [$a->id, $b->id],
                'signature' => self::PNG,
            ])
            ->assertOk()
            ->assertJsonPath('message', '2 Teilnehmer eingecheckt.');

        foreach ([$a, $b] as $booking) {
            $this->assertSame(self::PNG, $booking->fresh()->signature);
            $this->assertDatabaseHas('event_visits', ['adventure_id' => $adventure->id, 'player_id' => $booking->player_id]);
        }
        $this->assertNull($notSelected->fresh()->signature);
        $this->assertDatabaseMissing('event_visits', ['player_id' => $notSelected->player_id]);
    }

    public function test_bulk_checkin_skips_already_present_and_keeps_their_signature(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $present = $this->bookingFor($adventure, ['signature' => 'data:image/png;base64,ALT']);
        $adventure->visits()->create(['player_id' => $present->player_id]);
        $new = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->putJson(route('adventures.checkin.bulk', $adventure), [
                'booking_ids' => [$present->id, $new->id],
                'signature' => self::PNG,
            ])
            ->assertOk()
            ->assertJsonPath('message', '1 Teilnehmer eingecheckt. 1 bereits anwesend (übersprungen).');

        $this->assertSame('data:image/png;base64,ALT', $present->fresh()->signature);
        $this->assertSame(1, $adventure->visits()->where('player_id', $present->player_id)->count());
    }

    public function test_bulk_checkin_ignores_bookings_of_other_events(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $foreign = $this->bookingFor(Adventure::factory()->registrationClosed()->create());

        $this->actingAs($this->userWithRole(30))
            ->putJson(route('adventures.checkin.bulk', $adventure), [
                'booking_ids' => [$foreign->id],
                'signature' => self::PNG,
            ])
            ->assertStatus(422);

        $this->assertNull($foreign->fresh()->signature);
        $this->assertDatabaseMissing('event_visits', ['player_id' => $foreign->player_id]);
    }

    public function test_bulk_checkin_requires_closed_registration(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(30))
            ->putJson(route('adventures.checkin.bulk', $adventure), [
                'booking_ids' => [$booking->id],
                'signature' => self::PNG,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('event_visits', ['player_id' => $booking->player_id]);
    }

    public function test_bulk_actions_are_forbidden_without_permission(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $booking = $this->bookingFor($adventure);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('adventures.checkin.bulk', $adventure), [
                'booking_ids' => [$booking->id],
                'signature' => self::PNG,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->patchJson(route('adventures.deregister.bulk', $adventure), [
                'booking_ids' => [$booking->id],
                'absence_reason' => 'krank',
            ])
            ->assertForbidden();
    }

    public function test_bulk_deregister_sets_reason_and_removes_checkin(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $a = $this->bookingFor($adventure, ['status' => 'bestaetigt']);
        $b = $this->bookingFor($adventure, ['status' => 'bestaetigt']);
        $already = $this->bookingFor($adventure, ['status' => 'abgemeldet', 'absence_reason' => 'unentschuldigt']);
        $adventure->visits()->create(['player_id' => $a->player_id]);

        $this->actingAs($this->userWithRole(20))
            ->patchJson(route('adventures.deregister.bulk', $adventure), [
                'booking_ids' => [$a->id, $b->id, $already->id],
                'absence_reason' => 'krank',
            ])
            ->assertOk()
            ->assertJsonPath('message', '2 Teilnehmer abgemeldet (Krank). 1 bereits abgemeldet (übersprungen).');

        foreach ([$a, $b] as $booking) {
            $booking->refresh();
            $this->assertSame('abgemeldet', $booking->status);
            $this->assertSame('krank', $booking->absence_reason);
        }
        // Bereits Abgemeldete behalten ihren Grund.
        $this->assertSame('unentschuldigt', $already->fresh()->absence_reason);
        $this->assertDatabaseMissing('event_visits', ['player_id' => $a->player_id]);
    }

    public function test_checkin_tab_shows_selection_and_bulk_bar(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(20))
            ->get(route('adventures.manage', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('data-checkin-select', false)
            ->assertDontSee('data-checkin-select-all', false)
            ->assertSee('data-checkin-bulkbar hidden', false)
            ->assertSee(route('adventures.checkin.bulk', $adventure), false)
            ->assertSee(route('adventures.deregister.bulk', $adventure), false);
    }
}
