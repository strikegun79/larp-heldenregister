<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('players.index'))->assertRedirect(route('login'));
    }

    public function test_index_only_shows_the_users_own_players(): void
    {
        $user = User::factory()->create();
        $own = Player::factory()->create(['name' => 'Eigen']);
        $user->players()->attach($own->id, ['self' => true]);
        Player::factory()->create(['name' => 'Fremd']); // gehört niemandem

        $this->actingAs($user)->get(route('players.index'))
            ->assertOk()
            ->assertSee('Eigen')
            ->assertDontSee('Fremd');
    }

    public function test_creating_a_player_attaches_it_to_the_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('players.store'), [
            'name' => 'Mia',
            'lastname' => 'Klaiss',
            'gender' => 'weiblich',
            'self' => '1',
        ]);

        $player = Player::firstWhere('name', 'Mia');
        $this->assertNotNull($player);
        $this->assertTrue($user->players()->whereKey($player->id)->wherePivot('self', true)->exists());
    }

    public function test_ajax_edit_returns_modal_partial_and_update_returns_json(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['name' => 'Alt']);
        $user->players()->attach($player->id, ['self' => false]);

        $this->actingAs($user)
            ->get(route('players.edit', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('<!DOCTYPE html>', false)
            ->assertSee('data-modal-title', false);

        $this->actingAs($user)
            ->putJson(route('players.update', $player), ['name' => 'Neu', 'lastname' => 'Name'])
            ->assertOk()
            ->assertJson(['reload' => true]);

        $this->assertSame('Neu', $player->fresh()->name);
    }

    public function test_owner_can_set_and_replace_the_active_hero(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);
        $first = Hero::factory()->create(['player_id' => $player->id]);
        $second = Hero::factory()->create(['player_id' => $player->id]);

        $this->actingAs($user)
            ->patchJson(route('players.active-hero', $player), ['hero_id' => $first->id])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);
        $this->assertEquals($first->id, $player->fresh()->active_hero_id);

        // Nur ein aktiver Held: Setzen ersetzt den vorherigen.
        $this->actingAs($user)
            ->patchJson(route('players.active-hero', $player), ['hero_id' => $second->id])
            ->assertOk();
        $this->assertEquals($second->id, $player->fresh()->active_hero_id);
    }

    public function test_setting_active_hero_marks_only_that_hero_active(): void
    {
        // HERO-21: aktiv setzen aktiviert genau diesen Helden, alle anderen werden inaktiv.
        $user = User::factory()->create();
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);
        $first = Hero::factory()->create(['player_id' => $player->id, 'active' => true]);
        $second = Hero::factory()->create(['player_id' => $player->id, 'active' => true]);

        $this->actingAs($user)
            ->patchJson(route('players.active-hero', $player), ['hero_id' => $second->id])
            ->assertOk();

        $this->assertFalse($first->fresh()->active);
        $this->assertTrue($second->fresh()->active);
        $this->assertEquals($second->id, $player->fresh()->active_hero_id);
    }

    public function test_cannot_set_a_hero_of_another_player_as_active(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => false]);
        $foreignHero = Hero::factory()->create(); // gehört einem anderen Spieler

        $this->actingAs($user)
            ->patchJson(route('players.active-hero', $player), ['hero_id' => $foreignHero->id])
            ->assertStatus(422);

        $this->assertNull($player->fresh()->active_hero_id);
    }

    public function test_a_non_owner_cannot_set_the_active_hero(): void
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);

        $this->actingAs(User::factory()->create())
            ->patch(route('players.active-hero', $player), ['hero_id' => $hero->id])
            ->assertForbidden();
    }

    public function test_a_user_cannot_view_a_foreign_player(): void
    {
        $player = Player::factory()->create();
        $this->actingAs(User::factory()->create())
            ->get(route('players.show', $player))
            ->assertForbidden();
    }

    public function test_an_admin_can_view_any_player(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);
        $player = Player::factory()->create();

        $this->actingAs($admin)->get(route('players.show', $player))->assertOk();
    }

    public function test_a_user_can_delete_their_own_player(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => false]);

        $this->actingAs($user)->delete(route('players.destroy', $player))
            ->assertRedirect(route('players.index'));
        $this->assertSoftDeleted($player);
    }
}
