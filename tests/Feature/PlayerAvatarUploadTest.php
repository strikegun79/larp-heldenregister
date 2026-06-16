<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PLAY-11: Avatar-Upload (Crop-Editor, 20 MB Limit, 1:1 Speichern).
 */
class PlayerAvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    private function ownerOf(Player $player): User
    {
        $user = User::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);

        return $user;
    }

    public function test_owner_can_upload_valid_avatar(): void
    {
        $player = Player::factory()->create();
        $user = $this->ownerOf($player);

        $this->actingAs($user)
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $player->refresh();
        $this->assertNotNull($player->image);
        Storage::disk('public')->assertExists($player->image);
    }

    public function test_upload_rejects_file_over_20mb(): void
    {
        $player = Player::factory()->create();
        $user = $this->ownerOf($player);

        // UploadedFile::fake()->size() gibt die Größe in KB an.
        $this->actingAs($user)
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->image('big.jpg')->size(21000),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_upload_rejects_non_image(): void
    {
        $player = Player::factory()->create();
        $user = $this->ownerOf($player);

        $this->actingAs($user)
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_non_owner_cannot_upload_avatar(): void
    {
        $player = Player::factory()->create();
        $other = User::factory()->create();
        $other->roles()->attach(70); // Teilnehmer ohne Bezug zu diesem Spieler

        $this->actingAs($other)
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->image('avatar.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_old_avatar_is_replaced_not_duplicated(): void
    {
        $player = Player::factory()->create();
        $user = $this->ownerOf($player);

        // Erstes Bild hochladen.
        $this->actingAs($user)
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->image('first.jpg', 400, 400),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $firstPath = $player->refresh()->image;

        // Zweites Bild hochladen – das erste soll gelöscht sein.
        $this->actingAs($user)
            ->post(route('players.avatar', $player), [
                'image' => UploadedFile::fake()->image('second.jpg', 400, 400),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $player->refresh();
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($player->image);
    }

    public function test_player_card_shows_avatar_image(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);
        $player = Player::factory()->create(['image' => 'players/test.jpg']);
        $user->players()->attach($player->id, ['self' => false]);

        // Storage-Fake-Datei anlegen, damit avatar_url korrekt gebildet wird.
        Storage::disk('public')->put('players/test.jpg', 'fake');

        $this->actingAs($user)
            ->get(route('players.index'))
            ->assertOk()
            ->assertSee('players/test.jpg', false);
    }
}
