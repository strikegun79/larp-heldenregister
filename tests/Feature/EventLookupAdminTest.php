<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\EventCategory;
use App\Models\EventClient;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-09: Admin-CRUD für Event-Kategorien (Soft-Delete) und Auftraggeber.
 */
class EventLookupAdminTest extends TestCase
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

    public function test_admin_can_create_a_category(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.event-categories.store'), ['name' => 'Nachtspiel', 'description' => 'ab 16'])
            ->assertRedirect(route('admin.event-categories.index'));

        $this->assertDatabaseHas('event_categories', ['name' => 'Nachtspiel']);
    }

    public function test_category_name_is_required(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.event-categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_category_soft_delete_hides_it_from_event_form(): void
    {
        $category = EventCategory::create(['id' => 99, 'name' => 'Temporär']);

        $this->actingAs($this->userWithRole(10))
            ->delete(route('admin.event-categories.destroy', $category))
            ->assertRedirect();

        // Soft-Delete: Zeile bleibt, ist aber „trashed".
        $this->assertSoftDeleted('event_categories', ['id' => $category->id]);

        // Im Event-Formular nicht mehr wählbar.
        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.create'))
            ->assertOk()
            ->assertDontSee('Temporär');
    }

    public function test_admin_can_create_a_client(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.event-clients.store'), ['name' => 'Stadt Gießen'])
            ->assertRedirect(route('admin.event-clients.index'));

        $this->assertDatabaseHas('event_clients', ['name' => 'Stadt Gießen']);
    }

    public function test_client_in_use_cannot_be_deleted(): void
    {
        $client = EventClient::create(['id' => 99, 'name' => 'Benutzt']);
        Adventure::factory()->create(['event_client_id' => $client->id]);

        $this->actingAs($this->userWithRole(10))
            ->delete(route('admin.event-clients.destroy', $client))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('event_clients', ['id' => $client->id]);
    }

    public function test_unused_client_can_be_deleted(): void
    {
        $client = EventClient::create(['id' => 98, 'name' => 'Ungenutzt']);

        $this->actingAs($this->userWithRole(10))
            ->delete(route('admin.event-clients.destroy', $client))
            ->assertRedirect(route('admin.event-clients.index'));

        $this->assertDatabaseMissing('event_clients', ['id' => $client->id]);
    }

    public function test_non_admin_cannot_manage_lookups(): void
    {
        $this->actingAs($this->userWithRole(20)) // Bürokrat: kein portal.manage
            ->get(route('admin.event-categories.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole(20))
            ->get(route('admin.event-clients.index'))
            ->assertForbidden();
    }
}
