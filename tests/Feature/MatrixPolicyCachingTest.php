<?php

namespace Tests\Feature;

use App\Models\MatrixAccount;
use App\Models\MatrixManagedRoom;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatrixPolicyCachingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config([
            'matrix.domain'           => 'waldritter.de',
            'matrix.corporal_token'   => 'test-token',
            'matrix.corporal_cache_ttl' => 60,
        ]);
        Cache::forget(MatrixAccount::CORPORAL_CACHE_KEY);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);
        return $admin;
    }

    private function getPolicy(): \Illuminate\Testing\TestResponse
    {
        return $this->getJson('/api/matrix/corporal/policy', ['Authorization' => 'Bearer test-token']);
    }

    // --- Caching ---

    public function test_policy_wird_gecacht(): void
    {
        // Ersten Aufruf durchführen (befüllt Cache)
        $this->getPolicy()->assertOk();

        // Datenbankabfragen beim zweiten Aufruf messen
        DB::enableQueryLog();
        $this->getPolicy()->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Kein DB-Zugriff beim Cache-Hit
        $this->assertEmpty($queries, 'Zweiter Policy-Aufruf sollte keinen DB-Query auslösen.');
    }

    public function test_cache_wird_nach_konto_anlage_invalidiert(): void
    {
        // Cache befüllen
        $this->getPolicy()->assertJsonCount(0, 'users');

        // Konto anlegen
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $this->actingAs($this->admin())
            ->put(route('admin.players.matrix.update', $player), ['active' => '1'])
            ->assertRedirect();

        // Policy muss nach Invalidierung neues Konto zeigen
        $this->getPolicy()->assertJsonCount(1, 'users');
    }

    public function test_cache_wird_nach_konto_loeschung_invalidiert(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $admin  = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.players.matrix.update', $player), ['active' => '1']);

        // Cache befüllen (1 User)
        $this->getPolicy()->assertJsonCount(1, 'users');

        // Konto entziehen
        $this->actingAs($admin)
            ->delete(route('admin.players.matrix.destroy', $player))
            ->assertRedirect();

        // Policy darf das gelöschte Konto nicht mehr zeigen
        $this->getPolicy()->assertJsonCount(0, 'users');
    }

    public function test_cache_wird_nach_raum_anlage_invalidiert(): void
    {
        // Cache befüllen (keine Räume)
        $this->getPolicy()->assertJsonCount(0, 'managedRoomIds');

        // Raum anlegen (löst saved-Event aus → Cache-Invalidierung)
        MatrixManagedRoom::create(['roomid' => '!test:waldritter.de', 'roomname' => 'Test', 'roomtype' => 'Raum']);

        // Policy muss neuen Raum zeigen
        $this->getPolicy()->assertJsonCount(1, 'managedRoomIds');
    }

    public function test_cache_wird_nach_raum_loeschung_invalidiert(): void
    {
        $room = MatrixManagedRoom::create(['roomid' => '!test:waldritter.de', 'roomname' => 'Test', 'roomtype' => 'Raum']);

        // Cache befüllen (1 Raum)
        $this->getPolicy()->assertJsonCount(1, 'managedRoomIds');

        // Raum löschen
        $room->delete();

        // Policy darf gelöschten Raum nicht mehr zeigen
        $this->getPolicy()->assertJsonCount(0, 'managedRoomIds');
    }

    public function test_cache_wird_nach_raumaenderung_invalidiert(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $room   = MatrixManagedRoom::create(['roomid' => '!test:waldritter.de', 'roomname' => 'Test', 'roomtype' => 'Raum']);
        $admin  = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.players.matrix.update', $player), ['active' => '1']);

        // Cache befüllen: Konto ohne Räume
        $this->getPolicy()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.joinedRoomIds', []);

        // Raumzuordnung hinzufügen
        $this->actingAs($admin)->put(route('admin.players.matrix.update', $player), [
            'active' => '1',
            'rooms'  => ['!test:waldritter.de'],
        ])->assertRedirect();

        // Policy muss aktualisierte Räume zeigen
        $this->getPolicy()
            ->assertJsonPath('users.0.joinedRoomIds.0', '!test:waldritter.de');
    }
}
