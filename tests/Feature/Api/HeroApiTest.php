<?php

namespace Tests\Feature\Api;

use App\Models\Hero;
use App\Models\Player;
use Tests\TestCase;

/**
 * ARCH-007: Öffentliche Helden-API – Feldauswahl und Sichtbarkeitsregeln.
 */
class HeroApiTest extends TestCase
{
    // ----------------------------------------------------------------
    // Kein Auth erforderlich
    // ----------------------------------------------------------------

    public function test_index_antwortet_ohne_token(): void
    {
        $this->getJson('/api/v1/heroes')->assertOk();
    }

    public function test_show_antwortet_ohne_token(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_visible' => true]);

        $this->getJson("/api/v1/heroes/{$hero->public_code}")->assertOk();
    }

    // ----------------------------------------------------------------
    // Sichtbarkeit: nur public_visible=true
    // ----------------------------------------------------------------

    public function test_versteckter_held_nicht_in_index(): void
    {
        $player = Player::factory()->create();
        Hero::factory()->create(['player_id' => $player->id, 'public_visible' => false, 'character_name' => 'GeheimerHeld']);

        $response = $this->getJson('/api/v1/heroes');

        $response->assertOk();
        $response->assertJsonMissing(['character_name' => 'GeheimerHeld']);
    }

    public function test_versteckter_held_per_code_gibt_404(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_visible' => false]);

        $this->getJson("/api/v1/heroes/{$hero->public_code}")->assertNotFound();
    }

    // ----------------------------------------------------------------
    // Feldauswahl: kein Realname, keine interne ID
    // ----------------------------------------------------------------

    public function test_response_enthaelt_keine_realnamen(): void
    {
        $player = Player::factory()->create(['name' => 'Klaus', 'lastname' => 'Mustermann']);
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_visible' => true]);

        $response = $this->getJson("/api/v1/heroes/{$hero->public_code}");

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('Klaus', $content);
        $this->assertStringNotContainsString('Mustermann', $content);
    }

    public function test_response_enthaelt_keine_player_id(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_visible' => true]);

        $response = $this->getJson("/api/v1/heroes/{$hero->public_code}");

        $response->assertOk()
            ->assertJsonMissingPath('data.player_id')
            ->assertJsonMissingPath('data.id');
    }

    public function test_response_enthaelt_pflichtfelder(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_visible' => true]);

        $this->getJson("/api/v1/heroes/{$hero->public_code}")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'public_code', 'character_name', 'image_url', 'active',
            ]]);
    }

    // ----------------------------------------------------------------
    // Paginierung
    // ----------------------------------------------------------------

    public function test_index_liefert_paginierte_antwort(): void
    {
        $this->getJson('/api/v1/heroes')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }
}
