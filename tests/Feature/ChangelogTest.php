<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Changelog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Versionsverlauf: Seite aus CHANGELOG.md, nur für angemeldete Nutzer.
 */
class ChangelogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function user(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(10);

        return $user;
    }

    public function test_gaeste_werden_zum_login_umgeleitet(): void
    {
        $this->get(route('changelog'))->assertRedirect(route('login'));
    }

    public function test_angemeldete_nutzer_sehen_den_versionsverlauf(): void
    {
        $version = app(Changelog::class)->currentVersion();
        $this->assertNotNull($version, 'CHANGELOG.md enthält keine Version im Format „## [x.y.z]“.');

        $this->actingAs($this->user())
            ->get(route('changelog'))
            ->assertOk()
            ->assertSee('Versionsverlauf')
            ->assertSee('Aktuelle Version '.$version)
            ->assertSee('<h2>['.$version.']', false);
    }

    public function test_footer_verlinkt_den_versionsverlauf_mit_version(): void
    {
        $version = app(Changelog::class)->currentVersion();

        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('changelog'))
            ->assertSee('Version '.$version);
    }

    public function test_version_und_html_werden_aus_markdown_gelesen(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'changelog');
        file_put_contents($path, "# Versionsverlauf\n\n## [2.3.4] – 2026-01-01\n\n- Eintrag <script>alert(1)</script>\n");

        $changelog = new Changelog($path);

        $this->assertSame('2.3.4', $changelog->currentVersion());
        $this->assertStringNotContainsString('<h1>', $changelog->html());
        $this->assertStringNotContainsString('<script>', $changelog->html());
        $this->assertStringContainsString('<li>Eintrag', $changelog->html());

        unlink($path);
    }

    public function test_fehlende_datei_liefert_keine_version(): void
    {
        $changelog = new Changelog('/nicht/vorhanden/CHANGELOG.md');

        $this->assertNull($changelog->currentVersion());
        $this->assertSame('', $changelog->html());
    }
}
