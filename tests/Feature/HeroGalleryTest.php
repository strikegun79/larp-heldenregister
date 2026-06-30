<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroGalleryImage;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HERO-24: Helden-Galerie – bis zu 4 Bilder hochladen und löschen.
 */
class HeroGalleryTest extends TestCase
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

    public function test_registrar_kann_galerie_bild_hochladen(): void
    {
        $user = $this->registrar();
        $hero = Hero::factory()->create();
        $file = UploadedFile::fake()->image('galerie.jpg', 800, 600);

        $response = $this->actingAs($user)
            ->postJson(route('heroes.gallery.store', $hero), ['image' => $file]);

        $response->assertOk()
            ->assertJsonFragment(['refresh_modal' => true]);

        $this->assertDatabaseHas('hero_gallery_images', ['hero_id' => $hero->id]);
        $this->assertCount(1, Storage::disk('public')->files('heroes/gallery'));
    }

    public function test_maximale_anzahl_bilder_wird_durchgesetzt(): void
    {
        $user = $this->registrar();
        $hero = Hero::factory()->create();

        // 4 Bilder direkt anlegen
        HeroGalleryImage::factory()->count(4)->create(['hero_id' => $hero->id]);

        $file = UploadedFile::fake()->image('fuenftes.jpg');

        $response = $this->actingAs($user)
            ->postJson(route('heroes.gallery.store', $hero), ['image' => $file]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('hero_gallery_images', 4);
    }

    public function test_registrar_kann_galerie_bild_loeschen(): void
    {
        $user = $this->registrar();
        $hero = Hero::factory()->create();
        Storage::disk('public')->put('heroes/gallery/test.jpg', 'dummy');
        $image = HeroGalleryImage::create([
            'hero_id'    => $hero->id,
            'path'       => 'heroes/gallery/test.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('heroes.gallery.destroy', [$hero, $image]));

        $response->assertOk()
            ->assertJsonFragment(['refresh_modal' => true]);

        $this->assertDatabaseMissing('hero_gallery_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('heroes/gallery/test.jpg');
    }

    public function test_fremdes_bild_kann_nicht_geloescht_werden(): void
    {
        $user = $this->registrar();
        $hero1 = Hero::factory()->create();
        $hero2 = Hero::factory()->create();
        $image = HeroGalleryImage::create([
            'hero_id'    => $hero2->id,
            'path'       => 'heroes/gallery/fremdes.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('heroes.gallery.destroy', [$hero1, $image]));

        $response->assertForbidden();
        $this->assertDatabaseHas('hero_gallery_images', ['id' => $image->id]);
    }

    public function test_unauthenticated_user_kann_nicht_hochladen(): void
    {
        $hero = Hero::factory()->create();
        $file = UploadedFile::fake()->image('galerie.jpg');

        $this->postJson(route('heroes.gallery.store', $hero), ['image' => $file])
            ->assertUnauthorized();
    }

    public function test_galerie_bilder_werden_im_helden_detail_geladen(): void
    {
        $user = $this->registrar();
        $hero = Hero::factory()->create();
        HeroGalleryImage::create([
            'hero_id'    => $hero->id,
            'path'       => 'heroes/gallery/bild.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('heroes.show', $hero));

        $response->assertOk();
        // Galerie-Tab-Link muss im HTML erscheinen
        $response->assertSee('Galerie');
    }
}
