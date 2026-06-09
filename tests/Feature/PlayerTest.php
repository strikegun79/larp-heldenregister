<?php

namespace Tests\Feature;

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
