<?php

namespace Tests\Feature;

use App\Models\MatrixAccount;
use App\Models\MatrixManagedRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatrixCorporalTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-corporal-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['matrix.corporal_token' => $this->token]);
    }

    private function seedMatrix(): void
    {
        MatrixManagedRoom::create(['roomid' => '!a:wr.de', 'roomname' => 'Bibliothek', 'roomtype' => 'Raum', 'default_allow' => true]);
        MatrixManagedRoom::create(['roomid' => '!b:wr.de', 'roomname' => 'Krieger', 'roomtype' => 'Space']);

        $account = MatrixAccount::create([
            'mxid' => '@tester:wr.de',
            'display_name' => 'Tester',
            'auth_credential' => 'secret123',
            'active' => true,
            'forbid_room_creation' => true,
            'forbid_encrypted_room_creation' => true,
        ]);
        $account->rooms()->attach(['!a:wr.de', '!b:wr.de']);

        // Gelöschtes Konto darf nicht in der Policy auftauchen.
        MatrixAccount::create(['mxid' => '@geloescht:wr.de', 'active' => true])->delete();
    }

    public function test_policy_requires_a_valid_bearer_token(): void
    {
        $this->getJson('/api/matrix/corporal/policy')->assertUnauthorized();
        $this->getJson('/api/matrix/corporal/policy', ['Authorization' => 'Bearer falsch'])->assertUnauthorized();
    }

    public function test_policy_returns_the_corporal_document(): void
    {
        $this->seedMatrix();

        $response = $this->getJson('/api/matrix/corporal/policy', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('schemaVersion', 1)
            ->assertJsonPath('flags.allowCustomUserDisplayNames', false)
            ->assertJsonCount(2, 'managedRoomIds')
            ->assertJsonCount(2, 'hooks')
            ->assertJsonCount(1, 'users') // gelöschtes Konto ausgeschlossen
            ->assertJsonPath('users.0.id', '@tester:wr.de')
            ->assertJsonPath('users.0.authType', 'plain')
            ->assertJsonPath('users.0.authCredential', 'secret123')
            ->assertJsonPath('users.0.active', true)
            ->assertJsonCount(2, 'users.0.joinedRoomIds');
    }

    public function test_policy_is_denied_when_no_token_is_configured(): void
    {
        config(['matrix.corporal_token' => null]);

        $this->getJson('/api/matrix/corporal/policy', ['Authorization' => 'Bearer egal'])
            ->assertUnauthorized();
    }
}
