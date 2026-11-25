<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\PlotCharacter;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Helden, die im Heldenprofil zugestimmt haben, werden im Heldenverzeichnis vorgeschlagen. */
class PublicHeroSuggestedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    public function test_standardmaessig_nicht_vorgeschlagen(): void
    {
        $hero = Hero::factory()->create(['public_visible' => true]);

        $this->assertFalse($hero->fresh()->public_suggested);
        $this->get(route('public.hero.search'))
            ->assertOk()
            ->assertDontSee($hero->character_name);
    }

    public function test_vorgeschlagener_sichtbarer_held_erscheint_mit_link(): void
    {
        $hero = Hero::factory()->create([
            'character_name' => 'Aldric der Wanderer',
            'public_visible' => true,
            'public_suggested' => true,
        ]);

        $this->get(route('public.hero.search'))
            ->assertOk()
            ->assertSee('Bekannten Gestalten begegnen')
            ->assertSee('Aldric der Wanderer')
            ->assertSee(route('public.hero', $hero->public_code), false);
    }

    public function test_versteckter_held_wird_trotz_zustimmung_nicht_vorgeschlagen(): void
    {
        Hero::factory()->create([
            'character_name' => 'Heimlicher Held',
            'public_visible' => false,
            'public_suggested' => true,
        ]);

        $this->get(route('public.hero.search'))
            ->assertOk()
            ->assertDontSee('Heimlicher Held')
            ->assertDontSee('Bekannten Gestalten begegnen');
    }

    public function test_helden_und_plot_charaktere_gemischt_hoechstens_sechs(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            PlotCharacter::create(['name' => "Gestalt {$i}"]);
        }
        Hero::factory()->count(5)->create(['public_visible' => true, 'public_suggested' => true]);

        $suggestions = $this->get(route('public.hero.search'))->assertOk()->viewData('suggestions');

        $this->assertCount(6, $suggestions);
        $this->assertTrue($suggestions->every(fn ($s) => $s instanceof Hero || $s instanceof PlotCharacter));
    }

    public function test_betreuer_kann_vorschlag_umschalten(): void
    {
        $player = Player::factory()->create();
        $betreuer = User::factory()->create();
        $betreuer->players()->attach($player);
        $hero = Hero::factory()->create(['player_id' => $player->id, 'public_visible' => true]);

        $this->actingAs($betreuer)
            ->patch(route('heroes.suggested', $hero))
            ->assertRedirect();
        $this->assertTrue($hero->fresh()->public_suggested);

        $this->actingAs($betreuer)
            ->patch(route('heroes.suggested', $hero))
            ->assertRedirect();
        $this->assertFalse($hero->fresh()->public_suggested);
    }

    public function test_fremder_nutzer_kann_vorschlag_nicht_umschalten(): void
    {
        $hero = Hero::factory()->create(['public_visible' => true]);
        $other = User::factory()->create();
        $other->roles()->attach(70); // Teilnehmer

        $this->actingAs($other)
            ->patch(route('heroes.suggested', $hero))
            ->assertForbidden();

        $this->assertFalse($hero->fresh()->public_suggested);
    }

    public function test_schalter_im_heldenprofil_mit_hinweis(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10); // Admin
        $hero = Hero::factory()->create(['public_visible' => false, 'public_code' => 'SUGG01']);

        $this->actingAs($admin)
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('Im Heldenverzeichnis vorschlagen')
            ->assertSee('Wirkt erst, wenn das Profil öffentlich sichtbar ist.');
    }
}
