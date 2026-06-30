<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EpTransaction;
use App\Models\EpTransactionType;
use App\Models\Player;
use App\Models\TeamerSignup;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

/**
 * ADV-DELETE: Abenteuer löschen – Sperr-Logik und Berechtigungen.
 */
class AdventureDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    private function editor(): User
    {
        $user = User::factory()->create(['activated' => true]);
        $user->roles()->attach(20); // Bürokrat → events.edit

        return $user;
    }

    private function participant(): User
    {
        $user = User::factory()->create(['activated' => true]);
        $user->roles()->attach(70);

        return $user;
    }

    // ----------------------------------------------------------------
    // Berechtigungen
    // ----------------------------------------------------------------

    public function test_teilnehmer_kann_nicht_loeschen(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->participant())
            ->delete(route('adventures.destroy', $adventure))
            ->assertForbidden();

        $this->assertModelExists($adventure);
    }

    public function test_nicht_eingeloggter_nutzer_wird_weitergeleitet(): void
    {
        $adventure = Adventure::factory()->create();

        $this->delete(route('adventures.destroy', $adventure))
            ->assertRedirect(route('login'));

        $this->assertModelExists($adventure);
    }

    // ----------------------------------------------------------------
    // Erfolgreiches Löschen
    // ----------------------------------------------------------------

    public function test_leeres_abenteuer_kann_geloescht_werden(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'TestEvent']);

        $this->actingAs($this->editor())
            ->delete(route('adventures.destroy', $adventure))
            ->assertRedirect(route('adventures.manage-index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($adventure);
    }

    // ----------------------------------------------------------------
    // Sperre: Buchungen
    // ----------------------------------------------------------------

    public function test_abenteuer_mit_buchungen_kann_nicht_geloescht_werden(): void
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create();
        Booking::factory()->create([
            'adventure_id' => $adventure->id,
            'player_id' => $player->id,
        ]);

        $this->actingAs($this->editor())
            ->delete(route('adventures.destroy', $adventure))
            ->assertRedirect()
            ->assertSessionHas('error', 'Es gibt bereits Spieler-Anmeldungen für dieses Abenteuer.');

        $this->assertModelExists($adventure);
    }

    // ----------------------------------------------------------------
    // Sperre: Teamer-Anmeldungen
    // ----------------------------------------------------------------

    public function test_abenteuer_mit_teameranmeldungen_kann_nicht_geloescht_werden(): void
    {
        $adventure = Adventure::factory()->create();
        TeamerSignup::factory()->create([
            'adventure_id' => $adventure->id,
            'user_id' => User::factory()->create(['activated' => true])->id,
        ]);

        $this->actingAs($this->editor())
            ->delete(route('adventures.destroy', $adventure))
            ->assertRedirect()
            ->assertSessionHas('error', 'Es gibt bereits Teamer-Anmeldungen für dieses Abenteuer.');

        $this->assertModelExists($adventure);
    }

    // ----------------------------------------------------------------
    // Sperre: EP-Transaktionen
    // ----------------------------------------------------------------

    public function test_abenteuer_mit_ep_transaktionen_kann_nicht_geloescht_werden(): void
    {
        EpTransactionType::updateOrCreate(
            ['id' => 60],
            ['description' => 'Allgemein', 'is_credit' => true]
        );
        $adventure = Adventure::factory()->create();
        EpTransaction::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->editor())
            ->delete(route('adventures.destroy', $adventure))
            ->assertRedirect()
            ->assertSessionHas('error', 'Es wurden bereits EP-Transaktionen für dieses Abenteuer erfasst.');

        $this->assertModelExists($adventure);
    }

    // ----------------------------------------------------------------
    // Priorität der Sperrgründe
    // ----------------------------------------------------------------

    public function test_buchungen_haben_vorrang_vor_teameranmeldungen(): void
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create();
        Booking::factory()->create(['adventure_id' => $adventure->id, 'player_id' => $player->id]);
        TeamerSignup::factory()->create([
            'adventure_id' => $adventure->id,
            'user_id' => User::factory()->create(['activated' => true])->id,
        ]);

        $this->actingAs($this->editor())
            ->delete(route('adventures.destroy', $adventure))
            ->assertSessionHas('error', 'Es gibt bereits Spieler-Anmeldungen für dieses Abenteuer.');
    }

    // ----------------------------------------------------------------
    // deletionBlocker()-Methode direkt
    // ----------------------------------------------------------------

    public function test_deletion_blocker_gibt_null_fuer_leeres_abenteuer(): void
    {
        $adventure = Adventure::factory()->create();

        $this->assertNull($adventure->deletionBlocker());
    }

    public function test_deletion_blocker_gibt_grund_bei_buchungen(): void
    {
        $adventure = Adventure::factory()->create();
        Booking::factory()->create([
            'adventure_id' => $adventure->id,
            'player_id' => Player::factory()->create()->id,
        ]);

        $this->assertNotNull($adventure->deletionBlocker());
    }
}
