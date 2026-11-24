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
 * Pauschalen pro Veranstaltung (seit Issue #38 im Reiter Teamer/NSC der Event-Verwaltung).
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

    // ── Issue #38: Pauschalen im Reiter Teamer/NSC statt im Event-Formular ──

    public function test_event_form_has_no_compensation_fields_anymore(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create();

        foreach ([route('adventures.create'), route('adventures.edit', $adventure)] as $url) {
            $this->actingAs($this->admin())->get($url)->assertOk()
                ->assertDontSee('name="compensations[4]"', false)
                ->assertDontSee('Aufwandsentschädigung für Teamer');
        }
    }

    public function test_new_event_starts_with_default_compensations_of_billable_roles(): void
    {
        $this->billableRoles();

        $this->actingAs($this->admin())
            ->post(route('adventures.store'), $this->payload())
            ->assertRedirect();

        $adventure = Adventure::where('name', 'Sommerlager')->firstOrFail();
        $this->assertSame('35.00', $adventure->compensationFor(4));
        $this->assertSame('63.00', $adventure->compensationFor(6));
        $this->assertSame(2, $adventure->compensations()->count());
    }

    public function test_updating_event_data_leaves_compensations_untouched(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create(['event_status_id' => 30]);
        $adventure->compensations()->create(['event_role_id' => 4, 'amount' => 30]);

        $this->actingAs($this->admin())
            ->putJson(route('adventures.update', $adventure), $this->payload(['compensations' => [4 => '99']]))
            ->assertOk();

        $this->assertSame('30.00', $adventure->compensationFor(4));
    }

    public function test_teamer_tab_shows_form_above_table_with_prefill_or_saved_values(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create();

        // Ohne Einträge: Standard-Pauschalen vorbelegt, nur abrechnungsfähige Rollen.
        $html = $this->actingAs($this->admin())->get(route('adventures.manage', $adventure))->assertOk()->getContent();
        $this->assertStringContainsString('Aufwandsentschädigung für Teamer (€)', $html);
        $this->assertStringContainsString('Noch nicht gespeichert', $html);
        $this->assertStringContainsString('action="'.route('adventures.compensations.save', $adventure).'"', $html);
        $this->assertMatchesRegularExpression('/name="compensations\[4\]"[^>]*value="35.00"/', $html);
        $this->assertMatchesRegularExpression('/name="compensations\[6\]"[^>]*value="63.00"/', $html);
        $this->assertStringNotContainsString('name="compensations[3]"', $html);
        // Handy- und Desktop-Ausgabe mit eindeutigen IDs.
        $this->assertStringContainsString('id="comp-4-mob"', $html);
        $this->assertStringContainsString('id="comp-4-desk"', $html);
        // Formular steht im Reiter Teamer/NSC vor der Teamer-Tabelle.
        $tab = substr($html, strpos($html, 'data-tab="teamer-nsc"', strpos($html, 'tab segment')));
        $this->assertLessThan(strpos($tab, 'Teamer einladen'), strpos($tab, 'Pauschalen speichern'));

        // Mit Einträgen: gespeicherte Werte, fehlende Rolle leer statt Standard-Pauschale.
        $adventure->compensations()->create(['event_role_id' => 4, 'amount' => 30]);
        $html = $this->actingAs($this->admin())->get(route('adventures.manage', $adventure))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="compensations\[4\]"[^>]*value="30.00"/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="compensations\[6\]"[^>]*value="63.00"/', $html);
        $this->assertStringNotContainsString('Noch nicht gespeichert', $html);
    }

    public function test_save_compensations_syncs_amounts_and_empty_removes_entry(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson(route('adventures.compensations.save', $adventure), ['compensations' => [4 => '35', 6 => '63', 3 => '99']])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertSame('35.00', $adventure->compensationFor(4));
        $this->assertSame('63.00', $adventure->compensationFor(6));
        $this->assertNull($adventure->compensationFor(3)); // nicht abrechnungsfähig → ignoriert
        $this->assertSame(2, $adventure->compensations()->count());

        $this->actingAs($admin)
            ->putJson(route('adventures.compensations.save', $adventure), ['compensations' => [4 => '40', 6 => '']])
            ->assertOk();

        $this->assertSame('40.00', $adventure->compensationFor(4));
        $this->assertNull($adventure->compensationFor(6));
        $this->assertSame(1, $adventure->compensations()->count());
    }

    public function test_negative_amount_is_rejected(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->admin())
            ->putJson(route('adventures.compensations.save', $adventure), ['compensations' => [4 => '-5']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('compensations.4');
    }

    public function test_save_compensations_requires_events_edit(): void
    {
        $this->billableRoles();
        $adventure = Adventure::factory()->create();
        $teamer = User::factory()->create();
        $teamer->roles()->attach(50);

        $this->actingAs($teamer)
            ->putJson(route('adventures.compensations.save', $adventure), ['compensations' => [4 => '10']])
            ->assertForbidden();

        $this->assertSame(0, $adventure->compensations()->count());
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
