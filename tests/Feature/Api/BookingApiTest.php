<?php

namespace Tests\Feature\Api;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

/**
 * ARCH-007: Buchungs-API – Auth-Schutz, nur eigene Buchungen, Feldauswahl.
 */
class BookingApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    // ----------------------------------------------------------------
    // Auth-Schutz
    // ----------------------------------------------------------------

    public function test_ohne_token_gibt_401(): void
    {
        $this->getJson('/api/v1/me/bookings')->assertUnauthorized();
    }

    // ----------------------------------------------------------------
    // Nur eigene Buchungen
    // ----------------------------------------------------------------

    public function test_gibt_nur_eigene_buchungen_zurueck(): void
    {
        $user = User::factory()->create(['activated' => true]);
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        $adventure = Adventure::factory()->create();
        $ownBooking = Booking::factory()->create(['player_id' => $player->id, 'adventure_id' => $adventure->id]);

        // Buchung eines anderen Spielers
        $otherPlayer = Player::factory()->create();
        $otherBooking = Booking::factory()->create(['player_id' => $otherPlayer->id, 'adventure_id' => $adventure->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/bookings');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($ownBooking->id, $ids);
        $this->assertNotContains($otherBooking->id, $ids);
    }

    public function test_ohne_spieler_gibt_leere_liste(): void
    {
        $user = User::factory()->create(['activated' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/bookings')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    // ----------------------------------------------------------------
    // Feldauswahl: keine sensiblen Gesundheits-/Kontaktdaten
    // ----------------------------------------------------------------

    public function test_response_enthaelt_keine_sensiblen_felder(): void
    {
        $user = User::factory()->create(['activated' => true]);
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        $adventure = Adventure::factory()->create();
        Booking::factory()->create([
            'player_id' => $player->id,
            'adventure_id' => $adventure->id,
            'allergien' => 'Nüsse',
            'medikamente' => 'Penicillin',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/bookings');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('allergien', $content);
        $this->assertStringNotContainsString('medikamente', $content);
        $this->assertStringNotContainsString('erreichbarkeit', $content);
        $this->assertStringNotContainsString('kontakt_telefon', $content);
        $this->assertStringNotContainsString('signature', $content);
    }

    public function test_response_enthaelt_pflichtfelder(): void
    {
        $user = User::factory()->create(['activated' => true]);
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        $adventure = Adventure::factory()->create();
        Booking::factory()->create(['player_id' => $player->id, 'adventure_id' => $adventure->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/bookings')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'event_role', 'status', 'waitlisted', 'paid']]]);
    }

    // ----------------------------------------------------------------
    // Paginierung
    // ----------------------------------------------------------------

    public function test_liefert_paginierte_antwort(): void
    {
        $user = User::factory()->create(['activated' => true]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/bookings')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }
}
