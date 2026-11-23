<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\EventRole;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #19 (T1): abrechnungsfähige Rollen mit Standard-Pauschale und
 * Pauschalen pro Veranstaltung im Event-Formular.
 */
class TeamerCompensationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10);

        return $user;
    }

    /** @return array<string, mixed> */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Sommerlager',
            'kuerzel' => 'SoLa',
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-02 10:00',
            'event_status_id' => 30,
            'event_client_id' => 1,
            'event_category_id' => 0,
            'max_player' => 10,
            'fee' => 12,
        ], $extra);
    }

    private function billableRoles(): array
    {
        $b = EventRole::findOrFail(4);
        $b->update(['billable' => true, 'default_compensation' => 35]);
        $lehrmeister = EventRole::create([
            'id' => 6, 'description' => 'Lehrmeister', 'for_participant' => false, 'for_teamer' => true,
            'billable' => true, 'default_compensation' => 63,
        ]);

        return [$b, $lehrmeister];
    }

    public function test_admin_marks_role_billable_with_default_compensation(): void
    {
        $role = EventRole::findOrFail(3);

        $this->actingAs($this->admin())
            ->putJson(route('admin.event-roles.update', $role), [
                'description' => 'Teamer A', 'for_participant' => 0, 'for_teamer' => 1,
                'billable' => 1, 'default_compensation' => '35.00',
            ])
            ->assertOk();

        $role->refresh();
        $this->assertTrue($role->billable);
        $this->assertSame('35.00', $role->default_compensation);

        $this->actingAs($this->admin())->get(route('admin.event-roles.index'))
            ->assertOk()
            ->assertSee('abrechnungsfähig · 35,00 €');
    }

    public function test_event_form_prefills_defaults_for_new_event_and_lists_only_billable_roles(): void
    {
        $this->billableRoles();

        $html = $this->actingAs($this->admin())->get(route('adventures.create'))->assertOk()->getContent();

        $this->assertStringContainsString('Aufwandsentschädigung für Teamer (€)', $html);
        $this->assertMatchesRegularExpression('/name="compensations\[4\]"[^>]*value="35.00"/', $html);
        $this->assertMatchesRegularExpression('/name="compensations\[6\]"[^>]*value="63.00"/', $html);
        $this->assertStringNotContainsString('name="compensations[3]"', $html);
    }

    public function test_store_and_update_sync_amounts_and_empty_removes_entry(): void
    {
        $this->billableRoles();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('adventures.store'), $this->payload(['compensations' => [4 => '35', 6 => '63', 3 => '99']]))
            ->assertRedirect();

        $adventure = Adventure::where('name', 'Sommerlager')->firstOrFail();
        $this->assertSame('35.00', $adventure->compensationFor(4));
        $this->assertSame('63.00', $adventure->compensationFor(6));
        $this->assertNull($adventure->compensationFor(3)); // nicht abrechnungsfähig → ignoriert
        $this->assertSame(2, $adventure->compensations()->count());

        $this->actingAs($admin)
            ->putJson(route('adventures.update', $adventure), $this->payload(['compensations' => [4 => '40', 6 => '']]))
            ->assertOk();

        $this->assertSame('40.00', $adventure->compensationFor(4));
        $this->assertNull($adventure->compensationFor(6));
        $this->assertSame(1, $adventure->compensations()->count());
    }

    public function test_existing_event_without_entries_keeps_prefill_but_with_entries_shows_saved_values(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create();
        $adventure->compensations()->create(['event_role_id' => 4, 'amount' => 30]);

        $html = $this->actingAs($this->admin())->get(route('adventures.edit', $adventure))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="compensations\[4\]"[^>]*value="30.00"/', $html);
        // Lehrmeister hat hier keinen Eintrag → leer (keine Abrechnung), nicht die Standard-Pauschale
        $this->assertDoesNotMatchRegularExpression('/name="compensations\[6\]"[^>]*value="63.00"/', $html);
    }

    public function test_negative_amount_is_rejected(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->admin())
            ->putJson(route('adventures.update', $adventure), $this->payload(['compensations' => [4 => '-5']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('compensations.4');
    }

    public function test_compensation_requires_role_to_stay_billable(): void
    {
        [$b] = $this->billableRoles();
        $adventure = Adventure::factory()->create();
        $adventure->compensations()->create(['event_role_id' => 4, 'amount' => 35]);

        $b->update(['billable' => false]);

        $this->assertNull($adventure->compensationFor(4));
    }
}
