<?php

namespace Tests\Feature;

use App\Models\MatrixAccount;
use App\Models\MatrixManagedRoom;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatrixProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['matrix.domain' => 'waldritter-giessen.de', 'matrix.corporal_token' => 'test-token']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        return $admin;
    }

    public function test_non_admins_cannot_provision_matrix(): void
    {
        $player = Player::factory()->create();
        $this->actingAs(User::factory()->create())
            ->get(route('admin.players.matrix.edit', $player))
            ->assertForbidden();
    }

    public function test_admin_provisions_a_matrix_account_with_rooms(): void
    {
        $player = Player::factory()->create(['name' => 'Mia', 'lastname' => 'Klaiss']);
        $room = MatrixManagedRoom::create(['roomid' => '!a:waldritter-giessen.de', 'roomname' => 'Bibliothek', 'roomtype' => 'Raum']);

        $this->actingAs($this->admin())->put(route('admin.players.matrix.update', $player), [
            'active' => '1',
            'forbid_room_creation' => '1',
            'auth_credential' => 'geheim123',
            'rooms' => ['!a:waldritter-giessen.de'],
        ])->assertRedirect();

        $account = $player->fresh()->matrixAccount;
        $this->assertNotNull($account);
        $this->assertSame('@mia.klaiss:waldritter-giessen.de', $account->mxid);
        $this->assertTrue($account->active);
        $this->assertSame('geheim123', $account->auth_credential);
        $this->assertEquals(['!a:waldritter-giessen.de'], $account->rooms()->pluck('matrix_managed_rooms.roomid')->all());
    }

    public function test_provisioned_account_appears_in_the_corporal_policy(): void
    {
        $player = Player::factory()->create(['name' => 'Mia', 'lastname' => 'Klaiss']);

        $this->actingAs($this->admin())->put(route('admin.players.matrix.update', $player), [
            'active' => '1',
            'auth_credential' => 'geheim123',
        ]);

        $this->getJson('/api/matrix/corporal/policy', ['Authorization' => 'Bearer test-token'])
            ->assertOk()
            ->assertJsonPath('users.0.id', '@mia.klaiss:waldritter-giessen.de')
            ->assertJsonPath('users.0.authCredential', 'geheim123');
    }

    public function test_revoking_access_removes_the_user_from_the_policy(): void
    {
        $player = Player::factory()->create(['name' => 'Mia', 'lastname' => 'Klaiss']);
        $this->actingAs($this->admin())->put(route('admin.players.matrix.update', $player), ['active' => '1']);

        $this->actingAs($this->admin())->delete(route('admin.players.matrix.destroy', $player))->assertRedirect();

        $this->assertSoftDeleted('matrix_accounts', ['player_id' => $player->id]);
        $this->getJson('/api/matrix/corporal/policy', ['Authorization' => 'Bearer test-token'])
            ->assertOk()
            ->assertJsonCount(0, 'users');
    }

    public function test_existing_mxid_is_kept_on_rename(): void
    {
        $player = Player::factory()->create(['name' => 'Mia', 'lastname' => 'Klaiss']);
        MatrixAccount::create(['mxid' => '@mia.klaiss:waldritter-giessen.de', 'player_id' => $player->id, 'active' => true]);

        $player->update(['name' => 'Maria']);
        $this->actingAs($this->admin())->put(route('admin.players.matrix.update', $player), ['active' => '1']);

        // mxid bleibt stabile Matrix-Identität, ändert sich nicht bei Umbenennung.
        $this->assertSame('@mia.klaiss:waldritter-giessen.de', $player->fresh()->matrixAccount->mxid);
    }

    // MTX-06: Default-Raum-Zuordnung

    public function test_neues_konto_formular_zeigt_default_allow_räume_vorselektiert(): void
    {
        $player = Player::factory()->create();
        MatrixManagedRoom::create(['roomid' => '!allow:wr.de', 'roomname' => 'Standard',  'roomtype' => 'Raum', 'default_allow' => true]);
        MatrixManagedRoom::create(['roomid' => '!deny:wr.de',  'roomname' => 'Gesperrt',  'roomtype' => 'Raum', 'default_deny'  => true]);
        MatrixManagedRoom::create(['roomid' => '!none:wr.de',  'roomname' => 'Kein Flag', 'roomtype' => 'Raum']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.players.matrix.edit', $player));

        $response->assertOk();
        // default_allow-Raum ist vorselektiert
        $response->assertSee('value="!allow:wr.de"', false);
        $response->assertSee('checked', false);
        // Hinweistext für neues Konto
        $response->assertSee('Vorauswahl');
    }

    public function test_bestehendes_konto_behält_seine_räume_statt_defaults(): void
    {
        $player  = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $default = MatrixManagedRoom::create(['roomid' => '!allow:wr.de', 'roomname' => 'Standard', 'roomtype' => 'Raum', 'default_allow' => true]);
        $custom  = MatrixManagedRoom::create(['roomid' => '!custom:wr.de', 'roomname' => 'Speziell', 'roomtype' => 'Raum']);

        $account = MatrixAccount::create([
            'mxid'       => '@max.muster:waldritter-giessen.de',
            'player_id'  => $player->id,
            'active'     => true,
            'forbid_room_creation' => true,
            'forbid_encrypted_room_creation' => true,
        ]);
        // Konto hat nur den Custom-Raum (nicht den Default-Raum)
        $account->rooms()->attach($custom->roomid);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.players.matrix.edit', $player));

        $response->assertOk();
        // Kein "Vorauswahl"-Hinweis bei bestehendem Konto
        $response->assertDontSee('Vorauswahl');
    }

    public function test_neues_konto_speichert_nur_abgeschickte_räume(): void
    {
        $player  = Player::factory()->create(['name' => 'Lena', 'lastname' => 'Lenz']);
        $default = MatrixManagedRoom::create(['roomid' => '!allow:wr.de', 'roomname' => 'Standard', 'roomtype' => 'Raum', 'default_allow' => true]);
        $other   = MatrixManagedRoom::create(['roomid' => '!other:wr.de', 'roomname' => 'Anderer',  'roomtype' => 'Raum']);

        // Admin aktiviert Konto und schickt NUR den non-default Raum ab
        // (hat default_allow-Raum bewusst abgehakt)
        $this->actingAs($this->admin())->put(route('admin.players.matrix.update', $player), [
            'active' => '1',
            'rooms'  => ['!other:wr.de'],
        ])->assertRedirect();

        $account = $player->fresh()->matrixAccount;
        $this->assertNotNull($account);
        // Nur der explizit gewählte Raum
        $roomIds = $account->rooms()->pluck('matrix_managed_rooms.roomid')->all();
        $this->assertContains('!other:wr.de', $roomIds);
        $this->assertNotContains('!allow:wr.de', $roomIds);
    }
}
