<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reiter „Verwalten“ im Heldenbuch (V4): eine Fassung für Handy und Desktop,
 * Siegel-Feld mit Label und Hinweis, Status als Text, nur für Bearbeitungsrecht.
 */
class HeroManageTabWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_manage_section_has_labelled_code_field_unique_ids_and_status_text(): void
    {
        $registrar = User::factory()->create();
        $registrar->roles()->attach(20); // Bürokrat
        $hero = Hero::factory()->create(['died' => '2025-02-01', 'active' => false]);

        $html = $this->actingAs($registrar)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('<label for="hero-code-d" class="wr-label">Siegel-Code</label>', false)
            ->assertSee('aria-describedby="hero-code-d-hint"', false)
            ->assertSee('Als wiedergefunden markieren')
            ->assertSee('data-tab="manage"', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'id="hero-code-m"'));
        $this->assertSame(1, substr_count($html, 'id="hero-code-d"'));
        $this->assertStringNotContainsString('wr-verwalten-panel', $html);
    }

    public function test_owner_without_edit_right_does_not_see_manage_section(): void
    {
        $owner = User::factory()->create();
        $owner->roles()->attach(70);
        $hero = Hero::factory()->create();
        $owner->players()->attach($hero->player_id, ['self' => false]);

        $this->actingAs($owner)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('Siegel zuweisen')
            ->assertDontSee('data-tab="manage"', false);
    }
}
