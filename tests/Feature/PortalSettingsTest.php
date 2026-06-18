<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADM-09: Portal-Konfiguration (Key/Value).
 */
class PortalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, SettingsSeeder::class]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10);

        return $user;
    }

    // ── Setting-Model-Helper ───────────────────────────────────────────────

    public function test_setting_get_returns_default_when_missing(): void
    {
        $this->assertNull(Setting::get('nonexistent'));
        $this->assertSame('fallback', Setting::get('nonexistent', 'fallback'));
    }

    public function test_setting_set_creates_and_updates(): void
    {
        Setting::set('association_name', 'Testverein');
        $this->assertSame('Testverein', Setting::get('association_name'));

        Setting::set('association_name', 'Geänderter Verein');
        $this->assertSame('Geänderter Verein', Setting::get('association_name'));
        $this->assertDatabaseCount('settings', 3); // Seeder hat 3 Defaults gesetzt
    }

    // ── Seeder ─────────────────────────────────────────────────────────────

    public function test_seeder_creates_defaults(): void
    {
        $this->assertSame('Waldritter-Gießen e.V.', Setting::get('association_name'));
        $this->assertNotEmpty(Setting::get('contact_email'));
        $this->assertNotEmpty(Setting::get('portal_logo'));
    }

    // ── Admin-Ansicht ──────────────────────────────────────────────────────

    public function test_admin_can_view_settings(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Vereinsname')
            ->assertSee('Waldritter-Gießen e.V.');
    }

    public function test_admin_can_update_settings(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'association_name' => 'Neuer Verein e.V.',
                'contact_email' => 'neu@example.com',
                'portal_logo' => 'neues-logo.png',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame('Neuer Verein e.V.', Setting::get('association_name'));
        $this->assertSame('neu@example.com', Setting::get('contact_email'));
        $this->assertSame('neues-logo.png', Setting::get('portal_logo'));
    }

    public function test_contact_email_must_be_valid(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'association_name' => 'Verein',
                'contact_email' => 'keine-email',
                'portal_logo' => 'logo.png',
            ])
            ->assertSessionHasErrors('contact_email');
    }

    public function test_association_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'association_name' => '',
                'contact_email' => 'test@example.com',
                'portal_logo' => 'logo.png',
            ])
            ->assertSessionHasErrors('association_name');
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat

        $this->actingAs($user)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }
}
