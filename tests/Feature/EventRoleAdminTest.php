<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-10: Admin-CRUD für Teilnahme-Rollen; Verwendung im Buchungsformular.
 */
class EventRoleAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_admin_can_create_a_role(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.event-roles.store'), ['description' => 'Küche'])
            ->assertRedirect(route('admin.event-roles.index'));

        $this->assertDatabaseHas('event_roles', ['description' => 'Küche']);
    }

    public function test_description_is_required(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.event-roles.store'), ['description' => ''])
            ->assertSessionHasErrors('description');
    }

    public function test_role_in_use_cannot_be_deleted(): void
    {
        $adventure = Adventure::factory()->create();
        $role = EventRole::find(1); // Spieler
        Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'event_role_id' => $role->id,
        ]);

        $this->actingAs($this->userWithRole(10))
            ->delete(route('admin.event-roles.destroy', $role))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('event_roles', ['id' => $role->id]);
    }

    public function test_unused_role_can_be_deleted(): void
    {
        $role = EventRole::create(['id' => 99, 'description' => 'Ungenutzt']);

        $this->actingAs($this->userWithRole(10))
            ->delete(route('admin.event-roles.destroy', $role))
            ->assertRedirect(route('admin.event-roles.index'));

        $this->assertDatabaseMissing('event_roles', ['id' => $role->id]);
    }

    public function test_new_role_is_usable_in_booking_form(): void
    {
        $role = EventRole::create(['id' => 99, 'description' => 'Bardenrolle']);
        $player = Player::factory()->create();
        $booker = $this->userWithRole(60);
        $booker->players()->attach($player->id, ['self' => true]);
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($booker)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk()
            ->assertSee('Bardenrolle');
    }

    public function test_non_admin_cannot_manage_roles(): void
    {
        $this->actingAs($this->userWithRole(20)) // Bürokrat: kein portal.manage
            ->get(route('admin.event-roles.index'))
            ->assertForbidden();
    }
}
