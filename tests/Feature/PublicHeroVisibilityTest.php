<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-04: Tests für den Sichtbarkeits-Opt-out je Held. */
class PublicHeroVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_sichtbarer_held_ist_öffentlich_abrufbar(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'VISVVV', 'public_visible' => true]);

        $this->get(route('public.hero', 'VISVVV'))->assertOk();
    }

    public function test_versteckter_held_liefert_404(): void
    {
        Hero::factory()->create(['public_code' => 'HIDDEN', 'public_visible' => false]);

        $this->get(route('public.hero', 'HIDDEN'))->assertNotFound();
    }

    public function test_new_hero_ist_standardmässig_sichtbar(): void
    {
        $hero = Hero::factory()->create();

        $this->assertTrue($hero->public_visible);
    }

    public function test_admin_kann_sichtbarkeit_umschalten(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        $hero = Hero::factory()->create(['public_visible' => true]);

        $this->actingAs($user)
             ->patch(route('heroes.visibility', $hero));

        $this->assertFalse($hero->fresh()->public_visible);
    }

    public function test_admin_kann_sichtbarkeit_wieder_einschalten(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        $hero = Hero::factory()->create(['public_visible' => false]);

        $this->actingAs($user)
             ->patch(route('heroes.visibility', $hero));

        $this->assertTrue($hero->fresh()->public_visible);
    }

    public function test_gast_kann_sichtbarkeit_nicht_umschalten(): void
    {
        $hero = Hero::factory()->create(['public_visible' => true]);

        $this->patch(route('heroes.visibility', $hero))
             ->assertRedirect(route('login'));

        $this->assertTrue($hero->fresh()->public_visible);
    }

    public function test_edit_formular_setzt_public_visible(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        $hero = Hero::factory()->create(['public_visible' => true]);

        $this->actingAs($user)->patch(route('heroes.update', $hero), [
            'player_id'      => $hero->player_id,
            'character_name' => $hero->character_name,
            'public_visible' => '0',
        ]);

        $this->assertFalse($hero->fresh()->public_visible);
    }
}
