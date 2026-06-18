<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PLAY-11: Spieler-Detail mit Tabs, Avatar-/Helden-Foto-Upload (1:1), Abenteuer-Tab.
 */
class PlayerDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function ownerOf(Player $player): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);
        $user->players()->attach($player->id, ['self' => false]);

        return $user;
    }

    private function detail(User $user, Player $player)
    {
        return $this->actingAs($user)->get(route('players.show', $player), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_detail_has_tabs_and_age(): void
    {
        $player = Player::factory()->create(['dayofbirth' => now()->subYears(12)->toDateString()]);

        $response = $this->detail($this->ownerOf($player), $player)->assertOk();

        $response->assertSee('data-tab="p-allg"', false);
        $response->assertSee('data-tab="p-helden"', false);
        $response->assertSee('data-tab="p-abenteuer"', false);
        $response->assertSee('data-tab="p-avatar"', false);
        $response->assertSee('(12 Jahre)');
        // Papyrus-Hintergrund gehört auf die Kartei-Übersicht, nicht ins Detail (Korrektur).
        $response->assertDontSee('/images/player_background.png', false);
    }

    public function test_avatar_upload_is_cropped_to_square(): void
    {
        Storage::fake('public');
        $player = Player::factory()->create();
        $owner = $this->ownerOf($player);

        $this->actingAs($owner)
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->image('a.jpg', 600, 300), // nicht quadratisch
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $path = $player->fresh()->image;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame($w, $h); // 1:1
    }

    public function test_avatar_rejects_too_large_or_wrong_type(): void
    {
        Storage::fake('public');
        $player = Player::factory()->create();

        $this->actingAs($this->ownerOf($player))
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_abenteuer_tab_lists_visited_events(): void
    {
        $player = Player::factory()->create();
        $adventure = Adventure::factory()->create(['name' => 'Waldlauf']);
        $adventure->visits()->create(['player_id' => $player->id]);

        $this->detail($this->ownerOf($player), $player)
            ->assertOk()
            ->assertSee('Waldlauf')
            ->assertSee(route('adventures.show', $adventure), false);
    }

    public function test_registrar_can_upload_hero_photo(): void
    {
        Storage::fake('public');
        $hero = Hero::factory()->create(['player_id' => Player::factory()->create()->id]);

        $registrar = User::factory()->create();
        $registrar->roles()->attach(20); // Bürokrat: heldenregister.edit

        $this->actingAs($registrar)
            ->post(route('heroes.photo', $hero), [
                'image' => UploadedFile::fake()->image('hero.jpg', 500, 500),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertNotNull($hero->fresh()->image);
        Storage::disk('public')->assertExists($hero->fresh()->image);
    }

    public function test_player_owner_can_upload_hero_photo(): void
    {
        Storage::fake('public');
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);

        // Spieler-Eigentümer (Teilnehmer) darf seit HERO-22 das Helden-Foto ändern.
        $this->actingAs($this->ownerOf($player))
            ->post(route('heroes.photo', $hero), [
                'image' => UploadedFile::fake()->image('hero.jpg', 400, 400),
            ], ['Accept' => 'application/json'])
            ->assertOk();
    }

    public function test_owner_can_view_hero_detail_with_photo_upload(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'character_name' => 'Eldric']);

        // Eigentümer darf die Helden-Ansicht öffnen und seit HERO-22 auch das Foto hochladen.
        $this->actingAs($this->ownerOf($player))
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Eldric')
            ->assertSee(route('heroes.photo', $hero), false);
    }
}
