<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-01: Tests für den 6-stelligen öffentlichen Helden-Code. */
class HeroPublicCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_code_wird_beim_erstellen_automatisch_gesetzt(): void
    {
        $hero = Hero::factory()->create();

        $this->assertNotNull($hero->public_code);
    }

    public function test_code_hat_genau_sechs_zeichen(): void
    {
        $hero = Hero::factory()->create();

        $this->assertSame(6, strlen($hero->public_code));
    }

    public function test_code_verwendet_nur_erlaubte_zeichen(): void
    {
        $hero = Hero::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/',
            $hero->public_code,
        );
    }

    public function test_code_ist_datenbankseitig_eindeutig(): void
    {
        $hero1 = Hero::factory()->create();
        $hero2 = Hero::factory()->create();

        $this->assertNotEquals($hero1->public_code, $hero2->public_code);
    }

    public function test_generate_public_code_liefert_sechs_zeichen(): void
    {
        $code = Hero::generatePublicCode();

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression(
            '/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/',
            $code,
        );
    }

    public function test_manuell_gesetzter_code_wird_nicht_ueberschrieben(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'ABCDEF']);

        $this->assertSame('ABCDEF', $hero->public_code);
    }

    public function test_code_erscheint_in_helden_detailansicht(): void
    {
        $player = Player::factory()->create();
        $user   = User::factory()->create();
        $user->roles()->attach(10); // Admin
        $hero   = Hero::factory()->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)
            ->get(route('heroes.show', $hero));

        $response->assertOk()
                 ->assertSee($hero->public_code);
    }

    public function test_backfill_command_setzt_codes_fuer_bestehende_helden(): void
    {
        $hero = Hero::factory()->create();
        $hero->update(['public_code' => null]);
        $this->assertNull($hero->fresh()->public_code);

        $this->artisan('heroes:generate-codes')->assertSuccessful();

        $this->assertNotNull($hero->fresh()->public_code);
    }

    public function test_backfill_command_ueberspringt_helden_mit_code(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'ZZZZZZ']);

        $this->artisan('heroes:generate-codes')->assertSuccessful();

        $this->assertSame('ZZZZZZ', $hero->fresh()->public_code);
    }
}
