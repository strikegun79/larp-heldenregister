<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DSGVO Art. 30: Verzeichnis der Verarbeitungstätigkeiten – Zugriffsschutz (T-1).
 */
class ProcessingActivityAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        return $admin;
    }

    public function test_admin_can_view_processing_activities(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.processing-activities.index'))
            ->assertOk();
    }

    /** Issue #32: Eintrag 10 zur Teamer-Abrechnung ist im Verzeichnis enthalten. */
    public function test_processing_activities_contain_teamer_billing_entry(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.processing-activities.index'))
            ->assertOk()
            ->assertSeeInOrder([
                '9.',
                'Administratives Protokoll (Audit-Log)',
                '10.',
                'Teamer-Abrechnung (Aufwandsentschädigungen)',
                'Art. 6 Abs. 1 lit. c DSGVO',
            ])
            ->assertSee('verschlüsselte Speicherung von Bank- und Steuerdaten');
    }

    public function test_non_admin_cannot_view_processing_activities(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.processing-activities.index'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('admin.processing-activities.index'))
            ->assertRedirect(route('login'));
    }
}
