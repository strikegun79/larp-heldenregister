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
 * ADV-11: Spielleiter (gamemaster) und Eventleiter zuweisen + im Detail anzeigen.
 */
class EventStaffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId, array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->roles()->attach($roleId);

        return $user;
    }

    private function payloadFor(Adventure $adventure, array $extra = []): array
    {
        return array_merge([
            'name' => $adventure->name,
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-02 16:00',
            'event_status_id' => $adventure->event_status_id,
            'event_client_id' => $adventure->event_client_id,
            'event_category_id' => $adventure->event_category_id,
            'max_player' => 10,
            'fee' => 12,
        ], $extra);
    }

    public function test_can_assign_gamemaster_and_eventleader(): void
    {
        $admin = $this->userWithRole(10);
        $gm = $this->userWithRole(40, ['name' => 'Gandalf']);
        $leader = $this->userWithRole(30, ['name' => 'Elrond']);
        $adventure = Adventure::factory()->create();

        $this->actingAs($admin)
            ->putJson(route('adventures.update', $adventure), $this->payloadFor($adventure, [
                'gamemaster_id' => $gm->id,
                'eventleader_id' => $leader->id,
            ]))
            ->assertOk();

        $adventure->refresh();
        $this->assertSame($gm->id, $adventure->gamemaster_id);
        $this->assertSame($leader->id, $adventure->eventleader_id);
    }

    public function test_detail_shows_assignees(): void
    {
        $gm = $this->userWithRole(40, ['name' => 'Gandalf']);
        $adventure = Adventure::factory()->create(['gamemaster_id' => $gm->id]);

        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Spielleiter')
            ->assertSee('Gandalf');
    }

    public function test_form_lists_eligible_users_only(): void
    {
        $gm = $this->userWithRole(40, ['name' => 'Berechtigt']);
        $participant = $this->userWithRole(70, ['name' => 'Teilnehmender']);

        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.create'))
            ->assertOk()
            ->assertSee('Berechtigt')
            ->assertDontSee('Teilnehmender');
    }

    public function test_can_clear_assignees(): void
    {
        $gm = $this->userWithRole(40);
        $adventure = Adventure::factory()->create(['gamemaster_id' => $gm->id]);

        $this->actingAs($this->userWithRole(10))
            ->putJson(route('adventures.update', $adventure), $this->payloadFor($adventure, [
                'gamemaster_id' => '',
            ]))
            ->assertOk();

        $this->assertNull($adventure->fresh()->gamemaster_id);
    }
}
