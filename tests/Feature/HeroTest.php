<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
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

    public function test_a_registrar_can_delete_a_hero(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->delete(route('heroes.destroy', $hero))
            ->assertRedirect(route('heroes.index'));

        $this->assertModelMissing($hero);
    }
}
