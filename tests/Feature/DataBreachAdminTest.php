<?php

namespace Tests\Feature;

use App\Models\DataBreachLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DSGVO Art. 33/34: Admin-CRUD für das Datenpannen-Protokoll (T-1).
 */
class DataBreachAdminTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'discovered_at'            => '2026-01-15',
            'description'              => 'Unbefugter Zugriff auf Nutzerdaten.',
            'affected_data_categories' => ['kontaktdaten'],
            'affected_persons_count'   => 5,
            'likely_consequences'      => 'Identitätsdiebstahl möglich.',
            'measures_taken'           => 'Passwörter zurückgesetzt, Betroffene informiert.',
            'reportable'               => false,
            'reported_to_authority'    => false,
        ], $overrides);
    }

    public function test_admin_can_list_data_breaches(): void
    {
        $admin = $this->admin();
        DataBreachLog::create([
            ...$this->validPayload(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.data-breaches.index'))
            ->assertOk()
            ->assertSee('Unbefugter Zugriff auf Nutzerdaten.');
    }

    public function test_admin_can_see_the_create_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.data-breaches.create'))
            ->assertOk();
    }

    public function test_admin_can_store_a_data_breach(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.data-breaches.store'), $this->validPayload())
            ->assertRedirect(route('admin.data-breaches.index'));

        $this->assertDatabaseHas('data_breach_logs', [
            'description'        => 'Unbefugter Zugriff auf Nutzerdaten.',
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function test_store_requires_description(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.data-breaches.store'), $this->validPayload(['description' => '']))
            ->assertSessionHasErrors('description');
    }

    public function test_store_requires_discovered_at(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.data-breaches.store'), $this->validPayload(['discovered_at' => '']))
            ->assertSessionHasErrors('discovered_at');
    }

    public function test_store_requires_measures_taken(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.data-breaches.store'), $this->validPayload(['measures_taken' => '']))
            ->assertSessionHasErrors('measures_taken');
    }

    public function test_store_requires_at_least_one_data_category(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.data-breaches.store'), $this->validPayload(['affected_data_categories' => []]))
            ->assertSessionHasErrors('affected_data_categories');
    }

    public function test_store_rejects_invalid_data_category(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.data-breaches.store'), $this->validPayload(['affected_data_categories' => ['ungueltig']]))
            ->assertSessionHasErrors('affected_data_categories.0');
    }

    public function test_reported_at_is_required_when_reported_to_authority(): void
    {
        $payload = $this->validPayload([
            'reported_to_authority' => true,
            'reported_at'           => '',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.data-breaches.store'), $payload)
            ->assertSessionHasErrors('reported_at');
    }

    public function test_admin_can_view_a_data_breach(): void
    {
        $admin = $this->admin();
        $log = DataBreachLog::create([
            ...$this->validPayload(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.data-breaches.show', $log))
            ->assertOk()
            ->assertSee('Unbefugter Zugriff auf Nutzerdaten.');
    }

    public function test_admin_can_see_the_edit_form(): void
    {
        $admin = $this->admin();
        $log = DataBreachLog::create([
            ...$this->validPayload(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.data-breaches.edit', $log))
            ->assertOk();
    }

    public function test_admin_can_update_a_data_breach(): void
    {
        $admin = $this->admin();
        $log = DataBreachLog::create([
            ...$this->validPayload(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.data-breaches.update', $log), $this->validPayload([
                'description'   => 'Aktualisierte Beschreibung.',
                'measures_taken' => 'Neue Maßnahmen eingeleitet.',
            ]))
            ->assertRedirect(route('admin.data-breaches.show', $log));

        $this->assertDatabaseHas('data_breach_logs', [
            'id'          => $log->id,
            'description' => 'Aktualisierte Beschreibung.',
        ]);
    }

    public function test_non_admin_cannot_list_data_breaches(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.data-breaches.index'))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_store_a_data_breach(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.data-breaches.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_non_admin_cannot_update_a_data_breach(): void
    {
        $admin = $this->admin();
        $log = DataBreachLog::create([
            ...$this->validPayload(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.data-breaches.update', $log), $this->validPayload())
            ->assertForbidden();
    }
}
