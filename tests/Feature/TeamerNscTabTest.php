<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\TeamerSignup;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamerNscTabTest extends TestCase
{
    use RefreshDatabase;

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

    private function admin(): User
    {
        return $this->userWithRole(10);
    }

    private function adventure(): Adventure
    {
        return Adventure::factory()->create(['event_status_id' => 30]);
    }

    // ── Approve / Reject Toggle ───────────────────────────────────────────────

    public function test_admin_can_approve_teamer_signup(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->admin())
            ->patch(route('adventures.teamer.approve', [$adventure, $signup]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNotNull($signup->fresh()->approved_at);
        $this->assertNull($signup->fresh()->rejected_at);
    }

    public function test_approve_toggles_off(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create([
            'adventure_id' => $adventure->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->patch(route('adventures.teamer.approve', [$adventure, $signup]));

        $this->assertNull($signup->fresh()->approved_at);
    }

    public function test_admin_can_reject_teamer_signup(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->admin())
            ->patch(route('adventures.teamer.reject', [$adventure, $signup]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNotNull($signup->fresh()->rejected_at);
        $this->assertNull($signup->fresh()->approved_at);
    }

    public function test_reject_clears_approved_at(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create([
            'adventure_id' => $adventure->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->patch(route('adventures.teamer.reject', [$adventure, $signup]));

        $this->assertNull($signup->fresh()->approved_at);
        $this->assertNotNull($signup->fresh()->rejected_at);
    }

    public function test_teamer_cannot_approve_signup(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->userWithRole(50))
            ->patch(route('adventures.teamer.approve', [$adventure, $signup]))
            ->assertForbidden();
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function test_admin_can_open_edit_form(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->admin())
            ->get(route('adventures.teamer.edit', [$adventure, $signup]))
            ->assertOk()
            ->assertSee('Teamer bearbeiten');
    }

    public function test_admin_can_update_teamer_signup(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->admin())
            ->put(route('adventures.teamer.update', [$adventure, $signup]), [
                'teamer_role' => 'Teamer A',
                'kontakt_telefon' => '012 345 678',
                'allergien' => 'Nüsse',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('teamer_signups', [
            'id' => $signup->id,
            'teamer_role' => 'Teamer A',
            'allergien' => 'Nüsse',
        ]);
    }

    public function test_teamer_cannot_update_signup(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->userWithRole(50))
            ->put(route('adventures.teamer.update', [$adventure, $signup]), [
                'teamer_role' => 'Teamer A',
            ])
            ->assertForbidden();
    }

    // ── Manage-Modal zeigt NSC getrennt ──────────────────────────────────────

    public function test_manage_modal_separates_nsc_from_regular_bookings(): void
    {
        $adventure = $this->adventure();
        $player = Player::factory()->create();

        // Reguläre Buchung (Spieler, role_id=1)
        Booking::factory()->create([
            'adventure_id' => $adventure->id,
            'player_id' => $player->id,
            'event_role_id' => 1,
        ]);

        // NSC-Elternteil-Buchung (role_id=2)
        Booking::factory()->create([
            'adventure_id' => $adventure->id,
            'player_id' => Player::factory()->create()->id,
            'event_role_id' => 2,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('adventures.manage', $adventure));

        // Manage-Modal zeigt beide Buchungen insgesamt, aber getrennt
        $response->assertOk();
        // Tab-Beschriftung: Anmeldungen (1) — ohne NSC
        $response->assertSee('Anmeldungen (1)');
        // Teamer/NSC (0+1)
        $response->assertSee('Teamer/NSC (1)');
    }

    // ── Status-Accessor ───────────────────────────────────────────────────────

    public function test_status_label_open(): void
    {
        $signup = new TeamerSignup;
        $this->assertSame('offen', $signup->status_label);
    }

    public function test_status_label_approved(): void
    {
        $signup = new TeamerSignup(['approved_at' => now()]);
        $this->assertSame('bestätigt', $signup->status_label);
    }

    public function test_status_label_rejected(): void
    {
        $signup = new TeamerSignup(['rejected_at' => now()]);
        $this->assertSame('abgelehnt', $signup->status_label);
    }
}
