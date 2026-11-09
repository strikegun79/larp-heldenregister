<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-08: Admin-CRUD für Veranstaltungsorte.
 */
class LocationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_admin_can_create_a_location(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.locations.store'), [
                'titel' => 'Burg Staufenberg',
                'plz' => '35460',
                'city' => 'Staufenberg',
                'address' => 'Burgweg 1',
                'gps' => '50.66, 8.73',
            ])
            ->assertRedirect(route('admin.locations.index'));

        $this->assertDatabaseHas('locations', [
            'titel' => 'Burg Staufenberg',
            'city' => 'Staufenberg',
        ]);
    }

    public function test_admin_can_update_a_location(): void
    {
        $location = Location::create(['titel' => 'Alt']);

        $this->actingAs($this->userWithRole(10))
            ->put(route('admin.locations.update', $location), ['titel' => 'Neu'])
            ->assertRedirect(route('admin.locations.index'));

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'titel' => 'Neu']);
    }

    public function test_title_is_required(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.locations.store'), ['titel' => ''])
            ->assertSessionHasErrors('titel');

        $this->assertDatabaseCount('locations', 0);
    }

    public function test_deleting_a_location_nulls_its_events(): void
    {
        $location = Location::create(['titel' => 'Zu löschen']);
        $adventure = Adventure::factory()->create(['location_id' => $location->id]);

        $this->actingAs($this->userWithRole(10))
            ->delete(route('admin.locations.destroy', $location))
            ->assertRedirect(route('admin.locations.index'));

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertNull($adventure->fresh()->location_id);
    }

    public function test_non_admin_cannot_manage_locations(): void
    {
        // Bürokrat verwaltet Events, hat aber kein portal.manage.
        $this->actingAs($this->userWithRole(20))
            ->get(route('admin.locations.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole(20))
            ->post(route('admin.locations.store'), ['titel' => 'X'])
            ->assertForbidden();
    }

    public function test_location_is_selectable_in_event_form(): void
    {
        $location = Location::create(['titel' => 'Wählbarer Ort']);

        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.create'))
            ->assertOk()
            ->assertSee('Wählbarer Ort');
    }
}
