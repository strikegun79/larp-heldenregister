<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MatrixAccount;
use App\Models\MatrixManagedRoom;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatrixAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['matrix.domain' => 'waldritter.de', 'matrix.corporal_token' => 'test-token']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);
        return $admin;
    }

    public function test_konto_anlage_schreibt_audit_log(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $room   = MatrixManagedRoom::create(['roomid' => '!x:waldritter.de', 'roomname' => 'Test', 'roomtype' => 'Raum']);

        $this->actingAs($this->admin())
            ->put(route('admin.players.matrix.update', $player), [
                'active' => '1',
                'rooms'  => ['!x:waldritter.de'],
            ])->assertRedirect();

        $log = AuditLog::where('action', 'matrix.account.created')->first();
        $this->assertNotNull($log);
        $this->assertSame('@max.muster:waldritter.de', $log->subject_label);
        $this->assertSame('@max.muster:waldritter.de', $log->changes['mxid']);
        $this->assertTrue($log->changes['active']);
        $this->assertContains('!x:waldritter.de', $log->changes['rooms']);
    }

    public function test_konto_aktualisierung_schreibt_audit_log(): void
    {
        $player  = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $account = MatrixAccount::create([
            'mxid'      => '@max.muster:waldritter.de',
            'player_id' => $player->id,
            'active'    => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.players.matrix.update', $player), ['active' => '0'])
            ->assertRedirect();

        $log = AuditLog::where('action', 'matrix.account.updated')->first();
        $this->assertNotNull($log);
        $this->assertSame('@max.muster:waldritter.de', $log->subject_label);
        $this->assertTrue($log->changes['active']['von']);
        $this->assertFalse($log->changes['active']['auf']);
    }

    public function test_zugang_entziehen_schreibt_audit_log(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        MatrixAccount::create([
            'mxid'      => '@max.muster:waldritter.de',
            'player_id' => $player->id,
            'active'    => true,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.players.matrix.destroy', $player))
            ->assertRedirect();

        $log = AuditLog::where('action', 'matrix.account.revoked')->first();
        $this->assertNotNull($log);
        $this->assertSame('@max.muster:waldritter.de', $log->subject_label);
    }

    public function test_kein_audit_log_wenn_kein_zugang_vergeben(): void
    {
        $player = Player::factory()->create();

        // Kein active → Controller gibt ohne Aktion zurück
        $this->actingAs($this->admin())
            ->put(route('admin.players.matrix.update', $player), ['active' => '0'])
            ->assertRedirect();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    // --- Raumzahl in der Admin-Spielerliste (MTX-09) ---

    public function test_spielerliste_zeigt_raumzahl(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $room1  = MatrixManagedRoom::create(['roomid' => '!a:waldritter.de', 'roomname' => 'A', 'roomtype' => 'Raum']);
        $room2  = MatrixManagedRoom::create(['roomid' => '!b:waldritter.de', 'roomname' => 'B', 'roomtype' => 'Raum']);
        $account = MatrixAccount::create([
            'mxid'      => '@max.muster:waldritter.de',
            'player_id' => $player->id,
            'active'    => true,
        ]);
        $account->rooms()->attach(['!a:waldritter.de', '!b:waldritter.de']);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('admin.players.index'))
            ->assertOk()
            ->assertSee('2 Räume');
    }

    public function test_spielerliste_zeigt_einen_raum_singular(): void
    {
        $player = Player::factory()->create(['name' => 'Lena', 'lastname' => 'Lenz']);
        $room   = MatrixManagedRoom::create(['roomid' => '!c:waldritter.de', 'roomname' => 'C', 'roomtype' => 'Raum']);
        $account = MatrixAccount::create([
            'mxid'      => '@lena.lenz:waldritter.de',
            'player_id' => $player->id,
            'active'    => true,
        ]);
        $account->rooms()->attach('!c:waldritter.de');

        $this->actingAs($this->admin())
            ->get(route('admin.players.index'))
            ->assertOk()
            ->assertSee('1 Raum');
    }
}
