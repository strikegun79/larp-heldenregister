<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\TeamerSignup;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamerSignupTest extends TestCase
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

    private function teamer(): User
    {
        return $this->userWithRole(50);
    }

    private function lehrmeister(): User
    {
        return $this->userWithRole(45);
    }

    private function admin(): User
    {
        return $this->userWithRole(10);
    }

    private function adventure(): Adventure
    {
        return Adventure::factory()->create(['event_status_id' => 30]);
    }

    // ── Formular abrufen ──────────────────────────────────────────────────────

    public function test_teamer_can_fetch_signup_form(): void
    {
        $this->actingAs($this->teamer())
            ->get(route('adventures.teamer.create', $this->adventure()))
            ->assertOk()
            ->assertSee('Teamer-Anmeldung');
    }

    public function test_lehrmeister_can_fetch_signup_form(): void
    {
        $this->actingAs($this->lehrmeister())
            ->get(route('adventures.teamer.create', $this->adventure()))
            ->assertOk();
    }

    public function test_participant_cannot_fetch_signup_form(): void
    {
        $this->actingAs($this->userWithRole(70))
            ->get(route('adventures.teamer.create', $this->adventure()))
            ->assertForbidden();
    }

    // ── Anmelden ─────────────────────────────────────────────────────────────

    public function test_teamer_can_signup(): void
    {
        $adventure = $this->adventure();

        $this->actingAs($this->teamer())
            ->post(route('adventures.teamer.store', $adventure), [
                'agb' => '1',
                'kontakt_telefon' => '01234 56789',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('teamer_signups', ['adventure_id' => $adventure->id]);
    }

    public function test_teamer_cannot_signup_twice(): void
    {
        $adventure = $this->adventure();
        $teamer = $this->teamer();

        TeamerSignup::factory()->create([
            'adventure_id' => $adventure->id,
            'user_id' => $teamer->id,
        ]);

        $this->actingAs($teamer)
            ->post(route('adventures.teamer.store', $adventure), ['agb' => '1'])
            ->assertStatus(302); // redirect with error

        $this->assertDatabaseCount('teamer_signups', 1);
    }

    public function test_agb_is_required_to_signup(): void
    {
        $adventure = $this->adventure();

        $this->actingAs($this->teamer())
            ->post(route('adventures.teamer.store', $adventure), [])
            ->assertSessionHasErrors('agb');
    }

    // ── Stornieren ────────────────────────────────────────────────────────────

    public function test_teamer_can_cancel_own_signup(): void
    {
        $adventure = $this->adventure();
        $teamer = $this->teamer();
        $signup = TeamerSignup::factory()->create([
            'adventure_id' => $adventure->id,
            'user_id' => $teamer->id,
        ]);

        $this->actingAs($teamer)
            ->delete(route('adventures.teamer.destroy', [$adventure, $signup]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('teamer_signups', ['id' => $signup->id]);
    }

    public function test_teamer_cannot_cancel_others_signup(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->teamer())
            ->delete(route('adventures.teamer.destroy', [$adventure, $signup]))
            ->assertForbidden();
    }

    public function test_admin_can_cancel_any_signup(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->admin())
            ->delete(route('adventures.teamer.destroy', [$adventure, $signup]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('teamer_signups', ['id' => $signup->id]);
    }

    // ── Rollenzuweisung ───────────────────────────────────────────────────────

    public function test_admin_can_assign_teamer_role(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->admin())
            ->patch(route('adventures.teamer.update-role', [$adventure, $signup]), [
                'teamer_role' => 'Teamer A',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('teamer_signups', ['id' => $signup->id, 'teamer_role' => 'Teamer A']);
    }

    public function test_teamer_cannot_assign_role(): void
    {
        $adventure = $this->adventure();
        $teamer = $this->teamer();
        $signup = TeamerSignup::factory()->create([
            'adventure_id' => $adventure->id,
            'user_id' => $teamer->id,
        ]);

        $this->actingAs($teamer)
            ->patch(route('adventures.teamer.update-role', [$adventure, $signup]), [
                'teamer_role' => 'Teamer A',
            ])
            ->assertForbidden();
    }

    public function test_invalid_teamer_role_is_rejected(): void
    {
        $adventure = $this->adventure();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->admin())
            ->patch(route('adventures.teamer.update-role', [$adventure, $signup]), [
                'teamer_role' => 'Ungültige Rolle',
            ])
            ->assertSessionHasErrors('teamer_role');
    }
}
