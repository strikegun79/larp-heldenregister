<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Heldenformular (Team) im Waldritter-Design: Abschnitte, Startklassen nur bei Neuanlage,
 * Klassenverwaltung im Bearbeiten-Modal mit zugänglichen Entfernen-Buttons.
 */
class HeroFormWrTest extends TestCase
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
        $user->roles()->attach(20); // Bürokrat: heldenregister.edit

        return $user;
    }

    public function test_create_page_has_sections_and_start_classes(): void
    {
        HeroClass::create(['id' => 970, 'name' => 'Testklasse', 'slug' => 'test-970', 'ep_cost' => 5]);

        $this->actingAs($this->registrar())->get(route('heroes.create'))
            ->assertOk()
            ->assertSee('<h1 class="wr-heading">Neuer Held</h1>', false)
            ->assertSeeInOrder(['Zuordnung', 'Held', 'Daten', 'Startklassen', 'Freigabe und Sichtbarkeit'])
            ->assertSee('name="classes[]"', false)
            ->assertDontSee('name="active"', false);
    }

    public function test_edit_modal_has_class_management_with_accessible_buttons(): void
    {
        $class = HeroClass::create(['id' => 971, 'name' => 'Heiler', 'slug' => 'test-971', 'ep_cost' => 5]);
        $hero = Hero::factory()->create();
        $hero->classes()->attach($class->id);

        $this->actingAs($this->registrar())
            ->get(route('heroes.edit', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSee('id="hero-edit-form"', false)
            ->assertSee('aria-label="Klasse Heiler entfernen"', false)
            ->assertSee('name="active"', false)
            ->assertDontSee('Startklassen')
            ->assertSee('form="hero-edit-form"', false);
    }

    public function test_full_edit_page_has_danger_zone(): void
    {
        $hero = Hero::factory()->create(['character_name' => 'Ilva']);

        $this->actingAs($this->registrar())->get(route('heroes.edit', $hero))
            ->assertOk()
            ->assertSee('Zurück zum Heldenbuch')
            ->assertSee('Helden löschen')
            ->assertSee('data-confirm="Diesen Helden wirklich löschen?"', false);
    }
}
