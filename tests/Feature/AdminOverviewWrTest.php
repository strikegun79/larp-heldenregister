<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verwaltung im Waldritter-Design (V1): Übersicht mit H1/H2 und einheitlichen Kacheln,
 * Seitenkopf der übrigen Seiten mit echter H1.
 */
class AdminOverviewWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(10);

        return $user;
    }

    public function test_overview_has_heading_hierarchy_and_linked_cards(): void
    {
        $this->actingAs($this->admin())->get(route('admin.index'))
            ->assertOk()
            ->assertSee('<h1 class="wr-heading">Verwaltung</h1>', false)
            ->assertSeeInOrder(['Helden &amp; Spieler', 'Veranstaltungen', 'System &amp; Portal'], false)
            ->assertSee('href="'.route('admin.users.index').'"', false)
            ->assertSee('/images/verwaltung_portal-nutzer.jpg', false)
            ->assertSee('wr-card wr-card--link', false);
    }

    public function test_legacy_page_header_title_becomes_h1(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('class="wr-page-header"', false)
            ->getContent();

        $header = substr($html, strpos($html, 'class="wr-page-header"'), 1500);
        $this->assertStringContainsString('<h1', $header);
        $this->assertStringNotContainsString('<h2', $header);
    }

    public function test_non_admins_still_cannot_open_overview(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.index'))->assertForbidden();
    }
}
