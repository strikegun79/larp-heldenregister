<?php

namespace Tests\Feature\Api;

use App\Models\Adventure;
use App\Models\EventStatus;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

/**
 * ARCH-007: Adventure-API – Auth-Schutz, Sichtbarkeits-Scope, Feldauswahl.
 */
class AdventureApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    private function editor(): User
    {
        $user = User::factory()->create(['activated' => true]);
        $user->roles()->attach(20); // registrar → hat events.edit

        return $user;
    }

    private function participant(): User
    {
        $user = User::factory()->create(['activated' => true]);
        $user->roles()->attach(50); // teamer → events.view, kein events.edit

        return $user;
    }

    // ----------------------------------------------------------------
    // Auth-Schutz
    // ----------------------------------------------------------------

    public function test_index_ohne_token_gibt_401(): void
    {
        $this->getJson('/api/v1/adventures')->assertUnauthorized();
    }

    public function test_show_ohne_token_gibt_401(): void
    {
        $adventure = Adventure::factory()->create();

        $this->getJson("/api/v1/adventures/{$adventure->id}")->assertUnauthorized();
    }

    // ----------------------------------------------------------------
    // Grundfunktion
    // ----------------------------------------------------------------

    public function test_index_gibt_liste_zurueck(): void
    {
        Adventure::factory()->count(3)->create();
        $user = $this->editor();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/adventures')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_gibt_einzelnes_abenteuer_zurueck(): void
    {
        $adventure = Adventure::factory()->create();
        $user = $this->editor();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/adventures/{$adventure->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'start_at', 'fee', 'registration_open']]);
    }

    // ----------------------------------------------------------------
    // Feldauswahl: keine internen Felder
    // ----------------------------------------------------------------

    public function test_response_enthaelt_keine_internen_felder(): void
    {
        $adventure = Adventure::factory()->create(['is_hidden' => false]);
        $user = $this->editor();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/adventures/{$adventure->id}");

        $response->assertOk()
            ->assertJsonMissingPath('data.is_hidden')
            ->assertJsonMissingPath('data.reminder_sent_at')
            ->assertJsonMissingPath('data.function_email');
    }

    // ----------------------------------------------------------------
    // Sichtbarkeits-Scope: abgesagte/ausgeblendete Events
    // ----------------------------------------------------------------

    public function test_ausgeblendetes_event_nicht_in_index_fuer_teilnehmer(): void
    {
        Adventure::factory()->create(['is_hidden' => true, 'name' => 'AusgeblendetesEvent']);
        $user = $this->participant();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/adventures');

        $response->assertOk();
        $response->assertJsonMissing(['name' => 'AusgeblendetesEvent']);
    }

    public function test_abgesagtes_event_nicht_in_index_fuer_nicht_angemeldeten_teilnehmer(): void
    {
        Adventure::factory()->create([
            'name' => 'AbgesagtesEvent',
            'event_status_id' => EventStatus::CANCELLED,
        ]);
        $user = $this->participant();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/adventures');

        $response->assertOk();
        $response->assertJsonMissing(['name' => 'AbgesagtesEvent']);
    }

    public function test_verwalter_sieht_ausgeblendetes_event(): void
    {
        Adventure::factory()->create(['is_hidden' => true, 'name' => 'NurFuerVerwalter']);
        $user = $this->editor();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/adventures');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'NurFuerVerwalter']);
    }

    public function test_ausgeblendetes_event_show_gibt_404_fuer_teilnehmer(): void
    {
        $adventure = Adventure::factory()->create(['is_hidden' => true]);
        $user = $this->participant();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/adventures/{$adventure->id}")
            ->assertNotFound();
    }
}
