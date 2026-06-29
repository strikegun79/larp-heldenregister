<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * ARCH-006: PWA-Manifest, Service-Worker und App-Layout-Tags prüfen.
 *
 * Statische Dateien (manifest.webmanifest, sw.js, Icons) werden im
 * Testbetrieb nicht über den Laravel-Router ausgeliefert (das übernimmt
 * nginx/Apache). Deshalb wird ihr Inhalt direkt per Dateisystem geprüft.
 */
class PwaManifestTest extends TestCase
{
    // ----------------------------------------------------------------
    // manifest.webmanifest – Dateiinhalt
    // ----------------------------------------------------------------

    public function test_manifest_datei_existiert(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
    }

    public function test_manifest_ist_gueltiges_json(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertNotNull($data, 'manifest.webmanifest muss gültiges JSON sein');
    }

    public function test_manifest_enthaelt_pflichtfelder(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('short_name', $data);
        $this->assertArrayHasKey('start_url', $data);
        $this->assertArrayHasKey('display', $data);
        $this->assertArrayHasKey('icons', $data);
        $this->assertNotEmpty($data['icons']);
    }

    public function test_manifest_hat_waldritter_farben(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('#5a3a22', $data['theme_color'], 'Theme-Farbe muss Waldritter-Braun sein');
        $this->assertSame('#e4cea5', $data['background_color'], 'Hintergrundfarbe muss Pergament sein');
    }

    public function test_manifest_hat_icon_satz_mit_192_und_512(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $sizes = array_column($data['icons'], 'sizes');

        $this->assertContains('192x192', $sizes, 'Icon 192×192 fehlt');
        $this->assertContains('512x512', $sizes, 'Icon 512×512 fehlt');
    }

    public function test_manifest_hat_maskable_icon(): void
    {
        $data = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $purposes = array_column($data['icons'], 'purpose');

        $this->assertContains('maskable', $purposes, 'Maskierbares Icon fehlt im Manifest');
    }

    // ----------------------------------------------------------------
    // Statische Dateien – Existenz
    // ----------------------------------------------------------------

    public function test_service_worker_datei_existiert(): void
    {
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_icon_192_datei_existiert(): void
    {
        $this->assertFileExists(public_path('icons/icon-192.png'));
    }

    public function test_icon_512_datei_existiert(): void
    {
        $this->assertFileExists(public_path('icons/icon-512.png'));
    }

    public function test_icon_512_maskable_datei_existiert(): void
    {
        $this->assertFileExists(public_path('icons/icon-512-maskable.png'));
    }

    public function test_apple_touch_icon_datei_existiert(): void
    {
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
    }

    // ----------------------------------------------------------------
    // App-Layout enthält PWA-Tags (via authentifizierten Request)
    // ----------------------------------------------------------------

    public function test_app_layout_enthaelt_manifest_link(): void
    {
        $user = User::factory()->create(['activated' => true]);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('rel="manifest"', $content);
        $this->assertStringContainsString('/manifest.webmanifest', $content);
    }

    public function test_app_layout_enthaelt_theme_color_meta(): void
    {
        $user = User::factory()->create(['activated' => true]);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('name="theme-color"', $content);
        $this->assertStringContainsString('#5a3a22', $content);
    }

    public function test_app_layout_enthaelt_apple_touch_icon(): void
    {
        $user = User::factory()->create(['activated' => true]);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('apple-touch-icon', $content);
        $this->assertStringContainsString('/icons/apple-touch-icon.png', $content);
    }
}
