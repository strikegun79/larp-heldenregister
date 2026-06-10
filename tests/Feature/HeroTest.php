<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\HeroClassSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, HeroClassSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_guests_cannot_access_the_hero_register(): void
    {
        $this->get(route('heroes.index'))->assertRedirect(route('login'));
    }

    public function test_participants_cannot_access_the_hero_register(): void
    {
        $this->actingAs($this->userWithRole(70)) // Teilnehmer
            ->get(route('heroes.index'))
            ->assertForbidden();
    }

    public function test_a_viewer_role_sees_the_register_but_cannot_create(): void
    {
        Hero::factory()->create(['character_name' => 'Tilix']);
        $teamer = $this->userWithRole(50); // Teamer: sehen, nicht bearbeiten

        $this->actingAs($teamer)->get(route('heroes.index'))->assertOk()->assertSee('Tilix');
        $this->actingAs($teamer)->get(route('heroes.create'))->assertForbidden();
        $this->actingAs($teamer)->post(route('heroes.store'), [
            'player_id' => Player::factory()->create()->id,
            'character_name' => 'Neu',
        ])->assertForbidden();
    }

    public function test_overview_shows_player_ep_columns_and_no_edit_button(): void
    {
        $player = Player::factory()->create(['name' => 'Max', 'lastname' => 'Muster']);
        $hero = Hero::factory()->create(['player_id' => $player->id, 'character_name' => 'Tilix']);
        $hero->epTransactions()->create(['ep_transaction_type_id' => 10, 'ep_count' => 20]); // +20
        $hero->epTransactions()->create(['ep_transaction_type_id' => 20, 'ep_count' => 5]);  // -5

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.index'))
            ->assertOk()
            ->assertSeeInOrder(['Spieler', 'Charakter', 'EP gesamt', 'EP verfügbar', 'Klassen', 'Aktiv'])
            ->assertSee('Max Muster')
            ->assertSee('Tilix')
            ->assertSee('data-modal-url', false) // Zeile öffnet das Detail-Modal
            ->assertDontSee('Bearbeiten');        // kein Bearbeiten-Knopf in der Liste
    }

    public function test_detail_modal_loads_skilltree_for_a_class_with_skills(): void
    {
        // Regression: HeroClass::skills() nutzt die Pivot-Tabelle skill_hero_class
        // (nicht den Default-Namen hero_class_skill).
        $hero = Hero::factory()->create();
        $hero->classes()->attach(1); // Krieger
        $skill = Skill::create(['name' => 'Schwertkampf', 'ep_costs' => 2, 'perl_count' => 0]);
        $skill->classes()->attach(1);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Fertigkeitsbaum')
            ->assertSee('Schwertkampf')
            ->assertSee('skill-marker', false)            // HERO-16: Button auf dem Baum-Bild
            ->assertSee('data-skill-learned', false)      // Erlernt-Status am Trigger
            ->assertSeeInOrder(['Schwertkampf', 'Positionen bearbeiten']); // HERO-19: Button unter dem Baum
    }

    public function test_detail_modal_is_organized_in_tabs(): void
    {
        $hero = Hero::factory()->create();
        $hero->classes()->attach(1); // Krieger

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('data-tab="overview"', false)
            ->assertSee('data-tab="adventures"', false)
            ->assertSee('data-tab="cls-1"', false)
            ->assertSee('data-tab="ep"', false)
            ->assertSeeInOrder(['Übersicht', 'Abenteuer', 'Krieger', 'EP-Verlauf']);
    }

    public function test_ajax_show_returns_only_the_modal_partial(): void
    {
        $hero = Hero::factory()->create(['character_name' => 'Tilix']);
        $viewer = $this->userWithRole(40);

        // Normale Anfrage = volle Seite (mit Layout).
        $this->actingAs($viewer)->get(route('heroes.show', $hero))
            ->assertOk()->assertSee('<!DOCTYPE html>', false);

        // AJAX-Anfrage = nur der Modal-Inhalt (ohne Layout).
        $this->actingAs($viewer)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Tilix')
            ->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_ajax_edit_returns_modal_partial_and_update_returns_json(): void
    {
        $hero = Hero::factory()->create(['character_name' => 'Alt']);
        $registrar = $this->userWithRole(20);

        $this->actingAs($registrar)
            ->get(route('heroes.edit', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('<!DOCTYPE html>', false)
            ->assertSee('data-modal-title', false);

        $this->actingAs($registrar)
            ->putJson(route('heroes.update', $hero), [
                'player_id' => $hero->player_id,
                'character_name' => 'Neu',
            ])
            ->assertOk()
            ->assertJson(['reload' => true]);

        $this->assertSame('Neu', $hero->fresh()->character_name);
    }

    public function test_a_registrar_can_create_a_hero_with_classes(): void
    {
        $player = Player::factory()->create();

        $response = $this->actingAs($this->userWithRole(20)) // Registrar
            ->post(route('heroes.store'), [
                'player_id' => $player->id,
                'character_name' => 'Aldara',
                'classes' => [1, 4],
                'active' => '1',
            ]);

        $hero = Hero::firstWhere('character_name', 'Aldara');
        $this->assertNotNull($hero);
        $response->assertRedirect(route('heroes.show', $hero));
        $this->assertEqualsCanonicalizing([1, 4], $hero->classes->pluck('id')->all());
    }

    public function test_validation_rejects_a_hero_without_a_player(): void
    {
        $this->actingAs($this->userWithRole(20))
            ->post(route('heroes.store'), ['character_name' => 'Namenlos'])
            ->assertSessionHasErrors('player_id');
    }

    public function test_ep_balance_nets_credits_and_debits(): void
    {
        $hero = Hero::factory()->create();
        $hero->epTransactions()->create(['ep_transaction_type_id' => 10, 'ep_count' => 20]);
        $hero->epTransactions()->create(['ep_transaction_type_id' => 20, 'ep_count' => 5]);

        $this->assertEquals(15.0, $hero->fresh()->ep_balance);
    }

    public function test_registrar_can_toggle_missing_status(): void
    {
        $hero = Hero::factory()->create(['active' => true, 'died' => null]);
        $registrar = $this->userWithRole(20);

        $this->actingAs($registrar)
            ->patchJson(route('heroes.missing', $hero))
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $hero->refresh();
        $this->assertNotNull($hero->died);   // verschollen
        $this->assertFalse($hero->active);

        // wieder zurück (wiedergefunden)
        $this->actingAs($registrar)->patchJson(route('heroes.missing', $hero))->assertOk();
        $hero->refresh();
        $this->assertNull($hero->died);
        $this->assertTrue($hero->active);
    }

    public function test_a_viewer_cannot_toggle_missing(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(40))
            ->patch(route('heroes.missing', $hero))
            ->assertForbidden();
    }

    public function test_index_can_filter_missing_heroes(): void
    {
        Hero::factory()->create(['character_name' => 'Verschollener', 'died' => now()]);
        Hero::factory()->create(['character_name' => 'Lebender', 'died' => null]);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.index', ['status' => 'missing']))
            ->assertOk()
            ->assertSee('Verschollener')
            ->assertDontSee('Lebender');
    }

    public function test_index_search_matches_character_and_player_name(): void
    {
        $p1 = Player::factory()->create(['name' => 'Max', 'lastname' => 'Mustermann']);
        Hero::factory()->create(['player_id' => $p1->id, 'character_name' => 'Tilix']);
        $p2 = Player::factory()->create(['name' => 'Erika', 'lastname' => 'Beispiel']);
        Hero::factory()->create(['player_id' => $p2->id, 'character_name' => 'Aldebrand']);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.index', ['q' => 'Tilix']))
            ->assertOk()->assertSee('Tilix')->assertDontSee('Aldebrand');

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.index', ['q' => 'Mustermann']))
            ->assertOk()->assertSee('Tilix')->assertDontSee('Aldebrand');
    }

    public function test_index_filter_by_class(): void
    {
        $warrior = Hero::factory()->create(['character_name' => 'Kriegerheld']);
        $warrior->classes()->attach(1); // Krieger
        $wizard = Hero::factory()->create(['character_name' => 'Magierheld']);
        $wizard->classes()->attach(3); // Magier

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.index', ['class_id' => 1]))
            ->assertOk()->assertSee('Kriegerheld')->assertDontSee('Magierheld');
    }

    public function test_index_filter_by_player_and_status(): void
    {
        $player = Player::factory()->create();
        Hero::factory()->create(['player_id' => $player->id, 'character_name' => 'Eigenheld']);
        Hero::factory()->create(['character_name' => 'Fremdheld']);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.index', ['player_id' => $player->id]))
            ->assertOk()->assertSee('Eigenheld')->assertDontSee('Fremdheld');

        Hero::factory()->create(['character_name' => 'Inaktiverheld', 'active' => false, 'died' => null]);
        Hero::factory()->create(['character_name' => 'Aktiverheld', 'active' => true, 'died' => null]);

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.index', ['status' => 'inactive']))
            ->assertOk()->assertSee('Inaktiverheld')->assertDontSee('Aktiverheld');
    }

    public function test_form_uses_erste_erblickung_label(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.edit', $hero))
            ->assertOk()
            ->assertSee('Erste Erblickung')
            ->assertSee('Verschollen');
    }

    public function test_a_registrar_can_delete_a_hero(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->delete(route('heroes.destroy', $hero))
            ->assertRedirect(route('heroes.index'));

        $this->assertModelMissing($hero);
    }
}
