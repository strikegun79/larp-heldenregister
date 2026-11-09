<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HERO-09: Charakter-Steckbrief – Beschreibung und Avatar-Upload.
 */
class HeroSteckbriefTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat: heldenregister.edit

        return $user;
    }

    public function test_can_create_hero_with_description_and_image(): void
    {
        Storage::fake('public');
        $player = Player::factory()->create();

        $this->actingAs($this->registrar())
            ->post(route('heroes.store'), [
                'player_id' => $player->id,
                'character_name' => 'Thorgal',
                'description' => 'Ein Krieger aus dem Norden.',
                'image' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ])
            ->assertRedirect();

        $hero = Hero::firstWhere('character_name', 'Thorgal');
        $this->assertSame('Ein Krieger aus dem Norden.', $hero->description);
        $this->assertNotNull($hero->image);
        Storage::disk('public')->assertExists($hero->image);
    }

    public function test_image_must_be_a_valid_image(): void
    {
        Storage::fake('public');
        $player = Player::factory()->create();

        $this->actingAs($this->registrar())
            ->post(route('heroes.store'), [
                'player_id' => $player->id,
                'image' => UploadedFile::fake()->create('schaedlich.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_updating_image_replaces_the_old_file(): void
    {
        Storage::fake('public');
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);

        // Erstes Bild.
        $this->actingAs($this->registrar())
            ->put(route('heroes.update', $hero), [
                'player_id' => $player->id,
                'image' => UploadedFile::fake()->image('alt.jpg'),
            ])->assertRedirect();
        $old = $hero->fresh()->image;

        // Zweites Bild ersetzt das erste.
        $this->actingAs($this->registrar())
            ->put(route('heroes.update', $hero), [
                'player_id' => $player->id,
                'image' => UploadedFile::fake()->image('neu.jpg'),
            ])->assertRedirect();

        $new = $hero->fresh()->image;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_detail_shows_steckbrief(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create([
            'player_id' => $player->id,
            'description' => 'Legendäre Heldentaten.',
        ]);

        $this->actingAs($this->registrar())
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Steckbrief')
            ->assertSee('Legendäre Heldentaten.');
    }
}
