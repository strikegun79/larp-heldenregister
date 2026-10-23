<?php

namespace Tests\Feature;

use App\Models\Motd;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Startseiten-Nachrichten (MOTD) mit Verlauf: Anzeige auf dem Dashboard und Verwaltung.
 */
class MotdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sommerfest',
            'is_active' => '1',
            'dismissible' => '0',
            'variant' => 'info',
            'html' => '<p>Sommerfest am Samstag!</p>',
            'start_at' => null,
            'end_at' => null,
        ], $overrides);
    }

    private function motd(array $attributes = []): Motd
    {
        return Motd::create(array_merge([
            'title' => 'Sommerfest',
            'html' => '<p>Sommerfest am Samstag!</p>',
            'variant' => 'info',
            'is_active' => true,
            'content_updated_at' => now(),
        ], $attributes));
    }

    private function dashboard()
    {
        return $this->actingAs(User::factory()->create())->get(route('dashboard'));
    }

    // ── Anzeige ────────────────────────────────────────────────────────────

    public function test_active_motd_is_shown_on_dashboard(): void
    {
        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload())
            ->assertRedirect(route('admin.motd.index'));

        $this->dashboard()->assertOk()->assertSee('Sommerfest am Samstag!');
    }

    public function test_inactive_motd_is_hidden(): void
    {
        $this->motd(['is_active' => false]);

        $this->dashboard()->assertDontSee('Sommerfest am Samstag!');
    }

    public function test_motd_outside_time_window_is_hidden(): void
    {
        $motd = $this->motd(['start_at' => now()->addDay()]);
        $this->dashboard()->assertDontSee('Sommerfest am Samstag!');

        $motd->update(['start_at' => null, 'end_at' => now()->subDay()]);
        $this->dashboard()->assertDontSee('Sommerfest am Samstag!');
    }

    public function test_motd_inside_time_window_is_shown(): void
    {
        $this->motd(['start_at' => now()->subHour(), 'end_at' => now()->addHour()]);

        $this->dashboard()->assertSee('Sommerfest am Samstag!');
    }

    public function test_welcome_text_is_hidden_while_motd_is_shown(): void
    {
        $welcome = 'Willkommen im Heldenregister.';
        $this->dashboard()->assertSee($welcome);

        $motd = $this->motd();
        $this->dashboard()->assertDontSee($welcome);

        // Wegklickbar: Willkommenstext wird mitgeliefert und erscheint nach dem Schließen.
        $motd->update(['dismissible' => true]);
        $this->dashboard()->assertSee($welcome)->assertSee('motd-dismissed');
    }

    public function test_neutral_variant_uses_segment_colors(): void
    {
        $this->motd(['variant' => 'neutral']);

        $this->dashboard()
            ->assertSee('motd-neutral')
            ->assertSee('rgb(90 58 34 / 0.3)', false);
    }

    // ── Verwaltung: Liste, Anlegen, Bearbeiten ─────────────────────────────

    public function test_admin_sees_history_list(): void
    {
        $this->motd(['title' => 'Aktuelle Nachricht']);
        $this->motd(['title' => 'Alte Nachricht', 'is_active' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.motd.index'))
            ->assertOk()
            ->assertSee('Aktuelle Nachricht')
            ->assertSee('Alte Nachricht')
            ->assertSee('inaktiv');
    }

    public function test_admin_can_open_create_and_edit_pages(): void
    {
        $motd = $this->motd();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.motd.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.motd.edit', $motd))->assertOk()->assertSee('Sommerfest');
    }

    public function test_user_without_permission_cannot_manage(): void
    {
        $motd = $this->motd();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.motd.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.motd.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->post(route('admin.motd.activate', $motd))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.motd.destroy', $motd))->assertForbidden();

        $this->assertDatabaseCount('motds', 1);
    }

    public function test_script_tags_are_removed(): void
    {
        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload([
            'html' => '<p>Hallo</p><script>alert(1)</script>',
        ]));

        $html = Motd::first()->html;
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('Hallo', $html);
    }

    public function test_end_must_be_after_start(): void
    {
        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload([
            'start_at' => '2026-10-10T10:00',
            'end_at' => '2026-10-09T10:00',
        ]))->assertSessionHasErrors('end_at');
    }

    public function test_title_is_required(): void
    {
        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload(['title' => '']))
            ->assertSessionHasErrors('title');
    }

    // ── Nur eine aktive Nachricht ──────────────────────────────────────────

    public function test_activating_deactivates_previous_motd(): void
    {
        $old = $this->motd(['title' => 'Alt']);
        $new = $this->motd(['title' => 'Neu', 'is_active' => false, 'html' => '<p>Neue Nachricht</p>']);

        $this->actingAs($this->admin())->post(route('admin.motd.activate', $new))
            ->assertRedirect(route('admin.motd.index'));

        $this->assertFalse($old->fresh()->is_active);
        $this->assertTrue($new->fresh()->is_active);
        $this->dashboard()->assertSee('Neue Nachricht')->assertDontSee('Sommerfest am Samstag!');
    }

    public function test_storing_active_motd_deactivates_previous(): void
    {
        $old = $this->motd(['title' => 'Alt']);

        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload(['title' => 'Neu']));

        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame(1, Motd::where('is_active', true)->count());
    }

    public function test_deactivate(): void
    {
        $motd = $this->motd();

        $this->actingAs($this->admin())->post(route('admin.motd.deactivate', $motd));

        $this->assertFalse($motd->fresh()->is_active);
    }

    // ── Duplizieren, Löschen, Bild ─────────────────────────────────────────

    public function test_duplicate_creates_inactive_copy_with_own_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('motd/original.jpg', 'bild');
        $motd = $this->motd(['image' => 'motd/original.jpg', 'image_link' => 'https://example.org']);

        $response = $this->actingAs($this->admin())->post(route('admin.motd.duplicate', $motd));

        $copy = Motd::where('id', '!=', $motd->id)->first();
        $response->assertRedirect(route('admin.motd.edit', $copy));
        $this->assertSame('Kopie von Sommerfest', $copy->title);
        $this->assertFalse($copy->is_active);
        $this->assertSame($motd->html, $copy->html);
        $this->assertSame('https://example.org', $copy->image_link);
        $this->assertNotSame($motd->image, $copy->image);
        Storage::disk('public')->assertExists($copy->image);
        $this->assertTrue($motd->fresh()->is_active);
    }

    public function test_destroy_removes_motd_and_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('motd/banner.jpg', 'bild');
        $motd = $this->motd(['image' => 'motd/banner.jpg']);

        $this->actingAs($this->admin())->delete(route('admin.motd.destroy', $motd))
            ->assertRedirect(route('admin.motd.index'));

        $this->assertDatabaseMissing('motds', ['id' => $motd->id]);
        Storage::disk('public')->assertMissing('motd/banner.jpg');
    }

    public function test_image_upload_and_removal(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload([
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 320),
        ]));

        $motd = Motd::first();
        $this->assertNotNull($motd->image);
        Storage::disk('public')->assertExists($motd->image);
        $this->dashboard()->assertSee($motd->image_url);

        $path = $motd->image;
        $this->actingAs($this->admin())->put(route('admin.motd.update', $motd), $this->payload(['remove_image' => '1']));

        $this->assertNull($motd->fresh()->image);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_image_link_is_rendered(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload([
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 320),
            'image_link' => 'https://example.org/sommerfest',
        ]))->assertSessionHasNoErrors();

        $this->dashboard()
            ->assertSee('href="https://example.org/sommerfest"', false)
            ->assertSee('rel="noopener"', false);
    }

    public function test_javascript_image_link_is_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('admin.motd.store'), $this->payload([
            'image_link' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors('image_link');

        $this->assertDatabaseCount('motds', 0);
    }

    public function test_content_version_changes_only_when_content_changes(): void
    {
        $motd = $this->motd(['content_updated_at' => now()->subDay()]);
        $key = $motd->dismiss_key;
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.motd.update', $motd), $this->payload(['variant' => 'warning']));
        $this->assertSame($key, $motd->fresh()->dismiss_key);

        $this->actingAs($admin)->put(route('admin.motd.update', $motd), $this->payload(['html' => '<p>Neu</p>']));
        $this->assertNotSame($key, $motd->fresh()->dismiss_key);
    }
}
