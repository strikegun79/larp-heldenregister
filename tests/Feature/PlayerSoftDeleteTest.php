<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PLAY-08: Spieler-Soft-Delete + Wiederherstellung im Admin.
 */
class PlayerSoftDeleteTest extends TestCase
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

    public function test_admin_can_soft_delete_a_clean_player(): void
    {
        $player = Player::factory()->create(['name' => 'Sauber', 'lastname' => 'Held']);

        $this->actingAs($this->admin())
            ->delete(route('admin.players.destroy', $player->id))
            ->assertRedirect(route('admin.players.index'));

        $this->assertSoftDeleted('players', ['id' => $player->id]);
    }

    public function test_delete_warns_when_player_has_open_bookings(): void
    {
        $player = Player::factory()->create();
        $adventure = Adventure::factory()->create();
        Booking::factory()->for($adventure)->create(['player_id' => $player->id, 'status' => 'offen']);

        $response = $this->actingAs($this->admin())
            ->delete(route('admin.players.destroy', $player->id))
            ->assertRedirect(route('admin.players.index'));

        $response->assertSessionHas('warning');
        // Spieler wurde NICHT gelöscht.
        $this->assertNotSoftDeleted('players', ['id' => $player->id]);
    }

    public function test_delete_warns_when_player_has_active_heroes(): void
    {
        $player = Player::factory()->create();
        Hero::factory()->for($player)->create();

        $response = $this->actingAs($this->admin())
            ->delete(route('admin.players.destroy', $player->id))
            ->assertRedirect(route('admin.players.index'));

        $response->assertSessionHas('warning');
        $this->assertNotSoftDeleted('players', ['id' => $player->id]);
    }

    public function test_admin_can_force_delete_despite_blockers(): void
    {
        $player = Player::factory()->create();
        $adventure = Adventure::factory()->create();
        Booking::factory()->for($adventure)->create(['player_id' => $player->id, 'status' => 'bestaetigt']);

        $this->actingAs($this->admin())
            ->delete(route('admin.players.destroy', $player->id), ['force' => '1'])
            ->assertRedirect(route('admin.players.index'));

        $this->assertSoftDeleted('players', ['id' => $player->id]);
    }

    public function test_admin_can_restore_a_deleted_player(): void
    {
        $player = Player::factory()->create();
        $player->delete();
        $this->assertSoftDeleted('players', ['id' => $player->id]);

        $this->actingAs($this->admin())
            ->patch(route('admin.players.restore', $player->id))
            ->assertRedirect(route('admin.players.index'));

        $this->assertNotSoftDeleted('players', ['id' => $player->id]);
    }

    public function test_deleted_players_appear_in_admin_list(): void
    {
        $active = Player::factory()->create(['name' => 'Aktiv', 'lastname' => 'Held']);
        $deleted = Player::factory()->create(['name' => 'Geloescht', 'lastname' => 'Held']);
        $deleted->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.players.index'))
            ->assertOk()
            ->assertSee('Aktiv Held')
            ->assertSee('Geloescht Held');
    }

    public function test_non_admin_cannot_delete_player(): void
    {
        $player = Player::factory()->create();
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat: kein portal.manage

        $this->actingAs($user)
            ->delete(route('admin.players.destroy', $player->id))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_restore_player(): void
    {
        $player = Player::factory()->create();
        $player->delete();

        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat

        $this->actingAs($user)
            ->patch(route('admin.players.restore', $player->id))
            ->assertForbidden();
    }
}
