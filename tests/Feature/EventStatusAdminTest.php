<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\EventStatus;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADM-07: Admin-CRUD für Event-Status-Lookups.
 */
class EventStatusAdminTest extends TestCase
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

    public function test_admin_can_list_statuses(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.event-statuses.index'))
            ->assertOk()
            ->assertSee('Anmeldung offen')
            ->assertSee('Abgeschlossen');
    }

    public function test_admin_can_edit_description_and_color(): void
    {
        $status = EventStatus::find(10); // in Bearbeitung

        $this->actingAs($this->admin())
            ->put(route('admin.event-statuses.update', $status), [
                'description' => 'Entwurf',
                'color' => '#123456',
            ])
            ->assertRedirect(route('admin.event-statuses.index'));

        $status->refresh();
        $this->assertSame('Entwurf', $status->description);
        $this->assertSame('#123456', $status->color);
    }

    public function test_ajax_edit_returns_form_partial(): void
    {
        $status = EventStatus::find(30);

        $this->actingAs($this->admin())
            ->getJson(route('admin.event-statuses.edit', $status))
            ->assertOk()
            ->assertSee('Anmeldung offen', false);
    }

    public function test_ajax_update_returns_json(): void
    {
        $status = EventStatus::find(20);

        $this->actingAs($this->admin())
            ->putJson(route('admin.event-statuses.update', $status), [
                'description' => 'Planung',
                'color' => '#ffeb52',
            ])
            ->assertOk()
            ->assertJsonFragment(['reload' => true]);
    }

    public function test_description_is_required(): void
    {
        $status = EventStatus::find(10);

        $this->actingAs($this->admin())
            ->put(route('admin.event-statuses.update', $status), [
                'description' => '',
                'color' => '#ffffff',
            ])
            ->assertSessionHasErrors('description');
    }

    public function test_color_must_be_valid_hex(): void
    {
        $status = EventStatus::find(10);

        $this->actingAs($this->admin())
            ->put(route('admin.event-statuses.update', $status), [
                'description' => 'Test',
                'color' => 'rot',
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_status_in_use_cannot_be_deleted(): void
    {
        $status = EventStatus::find(30); // Anmeldung offen – hat Adventures via Factory

        Adventure::factory()->create(['event_status_id' => 30]);

        $this->actingAs($this->admin())
            ->delete(route('admin.event-statuses.destroy', $status))
            ->assertRedirect(route('admin.event-statuses.index'));

        $this->assertDatabaseHas('event_statuses', ['id' => 30]);
    }

    public function test_unused_status_can_be_deleted(): void
    {
        // Einen neuen Status anlegen, der nirgends verwendet wird.
        $status = EventStatus::create(['id' => 99, 'description' => 'Testlauf', 'color' => '#000000']);

        $this->actingAs($this->admin())
            ->delete(route('admin.event-statuses.destroy', $status))
            ->assertRedirect(route('admin.event-statuses.index'));

        $this->assertDatabaseMissing('event_statuses', ['id' => 99]);
    }

    public function test_non_admin_cannot_manage_statuses(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat: kein portal.manage

        $this->actingAs($user)
            ->get(route('admin.event-statuses.index'))
            ->assertForbidden();
    }
}
