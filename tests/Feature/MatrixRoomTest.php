<?php

namespace Tests\Feature;

use App\Models\MatrixAccount;
use App\Models\MatrixManagedRoom;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MTX-05: Admin-CRUD für Matrix-Räume.
 */
class MatrixRoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // admin
        return $user;
    }

    private function room(array $attrs = []): MatrixManagedRoom
    {
        return MatrixManagedRoom::create(array_merge([
            'roomid'        => '!test123:waldritter-giessen.de',
            'roomname'      => 'Waldritter Allgemein',
            'roomtype'      => 'Raum',
            'default_allow' => false,
            'default_deny'  => false,
        ], $attrs));
    }

    // --- Index ---

    public function test_admin_kann_raumliste_sehen(): void
    {
        $this->room();

        $this->actingAs($this->admin())
            ->get(route('admin.matrix.rooms.index'))
            ->assertOk()
            ->assertSee('Waldritter Allgemein');
    }

    public function test_gast_kann_raumliste_nicht_sehen(): void
    {
        $this->get(route('admin.matrix.rooms.index'))
            ->assertRedirect(route('login'));
    }

    public function test_normaler_nutzer_kann_raumliste_nicht_sehen(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(70); // participant

        $this->actingAs($user)
            ->get(route('admin.matrix.rooms.index'))
            ->assertForbidden();
    }

    // --- Create / Store ---

    public function test_admin_kann_raum_anlegen(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.matrix.rooms.store'), [
                'roomid'        => '!neu:waldritter-giessen.de',
                'roomname'      => 'Testkanal',
                'roomtype'      => 'Raum',
                'default_allow' => '1',
            ])
            ->assertRedirect(route('admin.matrix.rooms.index'));

        $this->assertDatabaseHas('matrix_managed_rooms', [
            'roomid'        => '!neu:waldritter-giessen.de',
            'roomname'      => 'Testkanal',
            'roomtype'      => 'Raum',
            'default_allow' => true,
            'default_deny'  => false,
        ]);
    }

    public function test_doppelte_room_id_wird_abgelehnt(): void
    {
        $this->room(['roomid' => '!doppelt:waldritter-giessen.de']);

        $this->actingAs($this->admin())
            ->post(route('admin.matrix.rooms.store'), [
                'roomid'   => '!doppelt:waldritter-giessen.de',
                'roomname' => 'Duplikat',
                'roomtype' => 'Raum',
            ])
            ->assertSessionHasErrors('roomid');
    }

    public function test_space_typ_ist_gueltig(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.matrix.rooms.store'), [
                'roomid'   => '!space:waldritter-giessen.de',
                'roomname' => 'Waldritter Space',
                'roomtype' => 'Space',
            ])
            ->assertRedirect(route('admin.matrix.rooms.index'));

        $this->assertDatabaseHas('matrix_managed_rooms', [
            'roomid'   => '!space:waldritter-giessen.de',
            'roomtype' => 'Space',
        ]);
    }

    public function test_ungültiger_typ_wird_abgelehnt(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.matrix.rooms.store'), [
                'roomid'   => '!x:waldritter-giessen.de',
                'roomname' => 'Test',
                'roomtype' => 'Ungültig',
            ])
            ->assertSessionHasErrors('roomtype');
    }

    // --- Edit / Update ---

    public function test_admin_kann_raum_bearbeiten(): void
    {
        $room = $this->room();

        $this->actingAs($this->admin())
            ->put(route('admin.matrix.rooms.update', $room), [
                'roomname'     => 'Umbenannt',
                'roomtype'     => 'Space',
                'default_deny' => '1',
            ])
            ->assertRedirect(route('admin.matrix.rooms.index'));

        $this->assertDatabaseHas('matrix_managed_rooms', [
            'roomid'       => $room->roomid,
            'roomname'     => 'Umbenannt',
            'roomtype'     => 'Space',
            'default_deny' => true,
        ]);
    }

    public function test_room_id_kann_nicht_geändert_werden(): void
    {
        $room = $this->room(['roomid' => '!original:waldritter-giessen.de']);

        // roomid wird im Update-Request ignoriert (nicht validiert)
        $this->actingAs($this->admin())
            ->put(route('admin.matrix.rooms.update', $room), [
                'roomid'   => '!geaendert:waldritter-giessen.de',
                'roomname' => 'Test',
                'roomtype' => 'Raum',
            ])
            ->assertRedirect(route('admin.matrix.rooms.index'));

        $this->assertDatabaseHas('matrix_managed_rooms', ['roomid' => '!original:waldritter-giessen.de']);
        $this->assertDatabaseMissing('matrix_managed_rooms', ['roomid' => '!geaendert:waldritter-giessen.de']);
    }

    // --- Destroy ---

    public function test_admin_kann_leeren_raum_löschen(): void
    {
        $room = $this->room();

        $this->actingAs($this->admin())
            ->delete(route('admin.matrix.rooms.destroy', $room))
            ->assertRedirect(route('admin.matrix.rooms.index'));

        $this->assertDatabaseMissing('matrix_managed_rooms', ['roomid' => $room->roomid]);
    }

    public function test_raum_mit_mitgliedern_kann_nicht_gelöscht_werden(): void
    {
        $room   = $this->room();
        $player = Player::factory()->create();
        $account = MatrixAccount::create([
            'mxid'       => '@test.user:waldritter-giessen.de',
            'player_id'  => $player->id,
            'active'     => true,
            'forbid_room_creation' => true,
            'forbid_encrypted_room_creation' => true,
        ]);
        $account->rooms()->attach($room->roomid);

        $this->actingAs($this->admin())
            ->delete(route('admin.matrix.rooms.destroy', $room))
            ->assertSessionHasErrors('roomid');

        $this->assertDatabaseHas('matrix_managed_rooms', ['roomid' => $room->roomid]);
    }
}
