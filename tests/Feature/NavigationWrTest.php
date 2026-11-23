<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hauptnavigation und untere Leiste im Waldritter-Design (UI-46): aria-current,
 * zugängliche Glocke, „Mehr“ als Dialog, kein abgeschalteter Fokus, Footer-Abstand.
 */
class NavigationWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function page(string $route = 'dashboard')
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(10);

        return $this->actingAs($user)->get(route($route));
    }

    public function test_active_item_is_marked_with_aria_current(): void
    {
        $html = $this->page('players.index')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/href="[^"]*\/players"[^>]*aria-current="page"|aria-current="page"[^>]*href="[^"]*\/players"/s', $html);
        $this->assertStringContainsString('wr-nav__link--active', $html);
        $this->assertStringNotContainsString('border-indigo-400', $html);
    }

    public function test_bell_has_accessible_name_and_focus_is_not_disabled_in_nav(): void
    {
        $html = $this->page()->assertOk()
            ->assertSee('aria-label="Benachrichtigungen, keine neuen"', false)
            ->assertSee('aria-label="Hauptnavigation"', false)
            ->getContent();

        $nav = substr($html, strpos($html, 'aria-label="Hauptnavigation"'), 6000);
        $this->assertStringNotContainsString('focus:outline-none"', $nav);
    }

    public function test_more_sheet_is_a_dialog_with_close_button(): void
    {
        $this->page()->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-labelledby="more-sheet-title"', false)
            ->assertSee('aria-label="Menü schließen"', false)
            ->assertSee('aria-haspopup="dialog"', false);
    }

    public function test_footer_is_not_hidden_behind_bottom_nav(): void
    {
        $this->page()->assertOk()
            ->assertSee('mt-8 mb-16 sm:mb-0', false);
    }
}
