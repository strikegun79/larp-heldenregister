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
 * HERO-22: Helden-Foto Upload, Löschen und Dummy-Bild.
 */
class HeroPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat: heldenregister.edit

        return $user;
    }

    public function test_hero_without_image_returns_default_url(): void
    {
        $hero = Hero::factory()->create(['image' => null]);

        $this->assertSame('/images/heroes_db.jpg', $hero->image_url);
    }

    public function test_hero_with_image_returns_storage_path(): void
    {
        $hero = Hero::factory()->create(['image' => 'heroes/test.jpg']);

        $this->assertSame('/storage/heroes/test.jpg', $hero->image_url);
    }

    public function test_registrar_can_upload_hero_photo(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->registrar())
            ->post(route('heroes.photo', $hero), [
                'image' => UploadedFile::fake()->image('hero.jpg', 400, 400),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $hero->refresh();
        $this->assertNotNull($hero->image);
        Storage::disk('public')->assertExists($hero->image);
    }

    public function test_upload_rejects_file_over_20mb(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->registrar())
            ->post(route('heroes.photo', $hero), [
                'image' => UploadedFile::fake()->image('big.jpg')->size(21000),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_registrar_can_delete_hero_photo(): void
    {
        Storage::disk('public')->put('heroes/old.jpg', 'fake');
        $hero = Hero::factory()->create(['image' => 'heroes/old.jpg']);

        $this->actingAs($this->registrar())
            ->delete(route('heroes.photo.destroy', $hero), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $hero->refresh();
        $this->assertNull($hero->image);
        Storage::disk('public')->assertMissing('heroes/old.jpg');
    }

    public function test_delete_on_hero_without_photo_is_harmless(): void
    {
        $hero = Hero::factory()->create(['image' => null]);

        $this->actingAs($this->registrar())
            ->delete(route('heroes.photo.destroy', $hero), [], ['Accept' => 'application/json'])
            ->assertOk();
    }

    public function test_participant_cannot_upload_hero_photo(): void
    {
        $hero = Hero::factory()->create();
        $user = User::factory()->create();
        $user->roles()->attach(70); // Teilnehmer: kein heldenregister.edit

        $this->actingAs($user)
            ->post(route('heroes.photo', $hero), [
                'image' => UploadedFile::fake()->image('hero.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_participant_cannot_delete_hero_photo(): void
    {
        $hero = Hero::factory()->create(['image' => 'heroes/test.jpg']);
        $user = User::factory()->create();
        $user->roles()->attach(70);

        $this->actingAs($user)
            ->delete(route('heroes.photo.destroy', $hero), [], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_player_detail_hero_tab_shows_default_image(): void
    {
        $owner = User::factory()->create();
        $owner->roles()->attach(70);
        $player = Player::factory()->create();
        $owner->players()->attach($player->id, ['self' => true]);
        Hero::factory()->for($player)->create(['image' => null]);

        $this->actingAs($owner)
            ->get(route('players.show', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('heroes_db.jpg', false);
    }
}
