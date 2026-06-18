<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-05: Geführter Status-Workflow – nur erlaubte Übergänge, Statusfarbe.
 */
class EventStatusWorkflowTest extends TestCase
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

    private function payloadFor(Adventure $adventure, int $statusId): array
    {
        return [
            'name' => $adventure->name,
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-02 16:00',
            'event_status_id' => $statusId,
            'event_client_id' => $adventure->event_client_id,
            'event_category_id' => $adventure->event_category_id,
            'max_player' => 10,
            'fee' => 12,
        ];
    }

    public function test_allowed_transition_succeeds(): void
    {
        $adventure = Adventure::factory()->create(); // Status 30 (Anmeldung offen)

        $this->actingAs($this->admin())
            ->putJson(route('adventures.update', $adventure), $this->payloadFor($adventure, 40))
            ->assertOk();

        $this->assertSame(40, $adventure->fresh()->event_status_id);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $adventure = Adventure::factory()->create(); // Status 30

        // 30 -> 60 (Abgeschlossen) ist kein erlaubter Sprung.
        $this->actingAs($this->admin())
            ->putJson(route('adventures.update', $adventure), $this->payloadFor($adventure, 60))
            ->assertStatus(422)
            ->assertJsonValidationErrors('event_status_id');

        $this->assertSame(30, $adventure->fresh()->event_status_id);
    }

    public function test_same_status_is_allowed(): void
    {
        $adventure = Adventure::factory()->create(); // Status 30

        $this->actingAs($this->admin())
            ->putJson(route('adventures.update', $adventure), $this->payloadFor($adventure, 30))
            ->assertOk();
    }

    public function test_allowed_status_ids_helper(): void
    {
        $adventure = Adventure::factory()->create(); // Status 30
        $this->assertEqualsCanonicalizing([30, 40, 70], $adventure->allowedStatusIds());

        $closed = Adventure::factory()->registrationClosed()->create(); // 40
        $this->assertEqualsCanonicalizing([40, 30, 50, 70], $closed->allowedStatusIds());
    }

    public function test_form_only_offers_allowed_statuses(): void
    {
        $adventure = Adventure::factory()->create(); // Status 30 -> erlaubt: 30/40/70

        $response = $this->actingAs($this->admin())
            ->get(route('adventures.manage', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        // Nicht erlaubte Ziel-Status erscheinen nicht im Dropdown.
        $response->assertDontSee('Abrechnung');   // 50
        $response->assertDontSee('Abgeschlossen'); // 60
        $response->assertDontSee('geplant');       // 20
    }

    public function test_status_color_badge_in_detail(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('#a2de00', false); // Farbe von „Anmeldung offen"
    }
}
