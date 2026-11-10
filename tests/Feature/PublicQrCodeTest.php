<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-05: Tests für QR-Code und Teilen-Funktionalität. */
class PublicQrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_canvas_mit_qr_url_im_helden_detail_bei_öffentlichem_held(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        $hero = Hero::factory()->create(['public_code' => 'QRTEST', 'public_visible' => true]);

        $this->actingAs($user)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('data-qr-url', false)
             ->assertSee(route('public.hero', 'QRTEST'), false);
    }

    public function test_kein_qr_canvas_bei_verstekctem_held(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        $hero = Hero::factory()->create(['public_code' => 'QRHIDE', 'public_visible' => false]);

        $this->actingAs($user)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertDontSee('data-qr-url', false);
    }

    public function test_öffentliches_profil_zeigt_link_kopieren_button(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'QRPUB1', 'public_visible' => true]);

        $this->get(route('public.hero', 'QRPUB1'))
             ->assertOk()
             ->assertSee('Link kopieren');
    }

    public function test_öffentliches_profil_zeigt_helden_code_als_text(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'QRPUB2', 'public_visible' => true]);

        $this->get(route('public.hero', 'QRPUB2'))
             ->assertOk()
             ->assertSee('QRPUB2');
    }

    public function test_öffentliche_profil_url_im_detail_sichtbar(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        $hero = Hero::factory()->create(['public_code' => 'QRURL5', 'public_visible' => true]);

        $this->actingAs($user)
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee(route('public.hero', 'QRURL5'), false);
    }
}
