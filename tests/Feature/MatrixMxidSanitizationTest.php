<?php

namespace Tests\Feature;

use App\Models\MatrixAccount;
use App\Models\Player;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatrixMxidSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['matrix.domain' => 'waldritter.de']);
    }

    // --- Sanitisierung: deriveMatrixId ---

    public function test_einfache_namen_bleiben_unveraendert(): void
    {
        $player = Player::factory()->make(['name' => 'Mia', 'lastname' => 'Klaiss']);
        $this->assertSame('@mia.klaiss:waldritter.de', $player->deriveMatrixId());
    }

    public function test_umlaute_werden_aufgeloest(): void
    {
        $player = Player::factory()->make(['name' => 'Björn', 'lastname' => 'Müller']);
        $this->assertSame('@bjoern.mueller:waldritter.de', $player->deriveMatrixId());
    }

    public function test_grossbuchstaben_umlaute_werden_aufgeloest(): void
    {
        $player = Player::factory()->make(['name' => 'Ärger', 'lastname' => 'Übel']);
        $this->assertSame('@aerger.uebel:waldritter.de', $player->deriveMatrixId());
    }

    public function test_eszett_wird_zu_ss(): void
    {
        $player = Player::factory()->make(['name' => 'Rüdiger', 'lastname' => 'Straße']);
        $this->assertSame('@ruediger.strasse:waldritter.de', $player->deriveMatrixId());
    }

    public function test_akzente_werden_zu_ascii(): void
    {
        $player = Player::factory()->make(['name' => 'François', 'lastname' => 'Dupont']);
        $this->assertSame('@francois.dupont:waldritter.de', $player->deriveMatrixId());
    }

    public function test_leerzeichen_werden_zu_unterstrich(): void
    {
        $player = Player::factory()->make(['name' => 'Mia Lenja', 'lastname' => 'von Muster']);
        $this->assertSame('@mia_lenja.von_muster:waldritter.de', $player->deriveMatrixId());
    }

    public function test_bindestrich_wird_zu_unterstrich(): void
    {
        $player = Player::factory()->make(['name' => 'Karl-Heinz', 'lastname' => 'Maier-Schmidt']);
        $this->assertSame('@karl_heinz.maier_schmidt:waldritter.de', $player->deriveMatrixId());
    }

    public function test_apostrophe_und_sonderzeichen_werden_entfernt(): void
    {
        $player = Player::factory()->make(['name' => "O'Brien", 'lastname' => 'Mc.Fly']);
        $this->assertSame('@obrien.mc.fly:waldritter.de', $player->deriveMatrixId());
    }

    public function test_leerer_name_faellt_auf_user_zurueck(): void
    {
        $player = Player::factory()->make(['name' => '!!!', 'lastname' => '???']);
        $this->assertSame('@user.user:waldritter.de', $player->deriveMatrixId());
    }

    // --- Kollisionsbehandlung: uniqueMatrixId ---

    public function test_unique_gibt_basis_wenn_kein_konflikt(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $this->assertSame('@max.muster:waldritter.de', $player->uniqueMatrixId());
    }

    public function test_unique_haengt_2_an_bei_kollision(): void
    {
        $other = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        MatrixAccount::create(['mxid' => '@max.muster:waldritter.de', 'player_id' => $other->id, 'active' => true]);

        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $this->assertSame('@max.muster2:waldritter.de', $player->uniqueMatrixId());
    }

    public function test_unique_haengt_3_an_wenn_2_ebenfalls_belegt(): void
    {
        $p1 = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $p2 = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        MatrixAccount::create(['mxid' => '@max.muster:waldritter.de',  'player_id' => $p1->id, 'active' => true]);
        MatrixAccount::create(['mxid' => '@max.muster2:waldritter.de', 'player_id' => $p2->id, 'active' => true]);

        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $this->assertSame('@max.muster3:waldritter.de', $player->uniqueMatrixId());
    }

    public function test_unique_ignoriert_eigene_mxid_bei_reaktivierung(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        // Spieler hat selbst eine mxid → kein Konflikt mit sich selbst
        MatrixAccount::create(['mxid' => '@max.muster:waldritter.de', 'player_id' => $player->id, 'active' => true]);

        $this->assertSame('@max.muster:waldritter.de', $player->uniqueMatrixId());
    }

    // --- Integration: Controller speichert sanitisierte mxid ---

    public function test_provisionierung_mit_umlaut_speichert_sanitisierte_mxid(): void
    {
        config(['matrix.corporal_token' => 'test-token']);

        $admin = \App\Models\User::factory()->create();
        $admin->roles()->attach(10);

        $player = Player::factory()->create(['name' => 'Björn', 'lastname' => 'Müller']);

        $this->actingAs($admin)
            ->put(route('admin.players.matrix.update', $player), ['active' => '1'])
            ->assertRedirect();

        $this->assertSame('@bjoern.mueller:waldritter.de', $player->fresh()->matrixAccount->mxid);
    }

    public function test_provisionierung_mit_leerzeichen_speichert_sanitisierte_mxid(): void
    {
        config(['matrix.corporal_token' => 'test-token']);

        $admin = \App\Models\User::factory()->create();
        $admin->roles()->attach(10);

        $player = Player::factory()->create(['name' => 'Mia Lenja', 'lastname' => 'von Muster']);

        $this->actingAs($admin)
            ->put(route('admin.players.matrix.update', $player), ['active' => '1'])
            ->assertRedirect();

        $this->assertSame('@mia_lenja.von_muster:waldritter.de', $player->fresh()->matrixAccount->mxid);
    }
}
