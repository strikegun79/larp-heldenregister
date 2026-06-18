<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ROLE-09: Lehrmeister-Rolle und Teamer-Änderung.
 */
class LehrmeisterRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    private function lehrmeister(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(45);

        return $user;
    }

    private function teamer(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(50);

        return $user;
    }

    // Lehrmeister-Berechtigungen

    public function test_lehrmeister_kann_heldenregister_einsehen(): void
    {
        $this->actingAs($this->lehrmeister())
            ->get(route('heroes.index'))
            ->assertOk();
    }

    public function test_lehrmeister_kann_helden_nicht_bearbeiten(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->lehrmeister())
            ->get(route('heroes.edit', $hero))
            ->assertForbidden();
    }

    public function test_lehrmeister_kann_abenteuer_buchen(): void
    {
        $this->actingAs($this->lehrmeister())
            ->get(route('adventures.index'))
            ->assertOk();
    }

    public function test_lehrmeister_hat_manage_attendance(): void
    {
        $this->assertTrue($this->lehrmeister()->can('manage-attendance'));
    }

    public function test_lehrmeister_hat_heldenregister_view(): void
    {
        $this->assertTrue($this->lehrmeister()->can('heldenregister.view'));
    }

    public function test_lehrmeister_hat_kein_heldenregister_edit(): void
    {
        $this->assertFalse($this->lehrmeister()->can('heldenregister.edit'));
    }

    public function test_lehrmeister_hat_kein_portal_manage(): void
    {
        $this->assertFalse($this->lehrmeister()->can('portal.manage'));
    }

    // Teamer-Änderung: kein heldenregister.view mehr

    public function test_teamer_kann_heldenregister_nicht_mehr_einsehen(): void
    {
        $this->actingAs($this->teamer())
            ->get(route('heroes.index'))
            ->assertForbidden();
    }

    public function test_teamer_hat_kein_heldenregister_view(): void
    {
        $this->assertFalse($this->teamer()->can('heldenregister.view'));
    }

    public function test_teamer_hat_weiterhin_manage_attendance(): void
    {
        $this->assertTrue($this->teamer()->can('manage-attendance'));
    }

    public function test_teamer_kann_abenteuer_sehen(): void
    {
        $this->actingAs($this->teamer())
            ->get(route('adventures.index'))
            ->assertOk();
    }
}
