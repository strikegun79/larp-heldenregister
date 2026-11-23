<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Heldenliste (Team) im Waldritter-Design: verknüpfte Filter-Labels, Status als Text,
 * Charaktername als Link statt Button-Zeile, Trefferzahl.
 */
class HeroListWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat

        return $user;
    }

    public function test_filters_have_linked_labels_and_result_count(): void
    {
        Hero::factory()->count(2)->create();

        $this->actingAs($this->registrar())->get(route('heroes.index'))
            ->assertOk()
            ->assertSee('<h1 class="wr-heading">Heldenverwaltung</h1>', false)
            ->assertSee('<label for="heroes-q"', false)
            ->assertSee('<label for="heroes-class"', false)
            ->assertSee('<label for="heroes-player"', false)
            ->assertSee('<label for="heroes-status"', false)
            ->assertSee('2 Helden gefunden');
    }

    public function test_missing_hero_status_is_text_and_row_is_not_dimmed(): void
    {
        Hero::factory()->create(['character_name' => 'Verschollener Ritter', 'died' => '2025-01-01', 'active' => false]);

        $html = $this->actingAs($this->registrar())->get(route('heroes.index'))
            ->assertOk()
            ->assertSee('verschollen')
            ->getContent();

        $this->assertStringNotContainsString('opacity-60', $html);
    }

    public function test_character_name_is_a_real_link_and_rows_are_no_buttons(): void
    {
        $hero = Hero::factory()->create(['character_name' => 'Tilix']);

        $html = $this->actingAs($this->registrar())->get(route('heroes.index'))
            ->assertOk()
            ->assertSee('href="'.route('heroes.show', $hero).'"', false)
            ->getContent();

        // Tabellenzeilen sind keine „Buttons“ mehr (Layout-Schließen-Symbole zählen nicht).
        $this->assertDoesNotMatchRegularExpression('/<tr[^>]*role="button"/', $html);
    }

    public function test_reset_only_with_active_filter_and_empty_filter_hint(): void
    {
        $user = $this->registrar();
        Hero::factory()->create(['character_name' => 'Tilix']);

        $this->actingAs($user)->get(route('heroes.index'))
            ->assertDontSee('Zurücksetzen');

        $this->actingAs($user)->get(route('heroes.index', ['q' => 'Gibtesnicht']))
            ->assertSee('Zurücksetzen')
            ->assertSee('Kein Held passt zu den Filtern.');
    }
}
