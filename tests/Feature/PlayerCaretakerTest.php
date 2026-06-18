<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PLAY-06: Admin verwaltet mehrere Betreuer je Spieler.
 */
class PlayerCaretakerTest extends TestCase
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

    public function test_admin_can_attach_a_caretaker(): void
    {
        $player = Player::factory()->create();
        $caretaker = User::factory()->create();

        $this->actingAs($this->admin())
            ->postJson(route('admin.players.caretakers.store', $player), ['user_id' => $caretaker->id])
            ->assertOk();

        $this->assertTrue($player->users()->whereKey($caretaker->id)->exists());
        // Betreuer sieht den Spieler in „Deine Spieler".
        $this->assertTrue($caretaker->players()->whereKey($player->id)->exists());
    }

    public function test_attaching_twice_does_not_duplicate(): void
    {
        $player = Player::factory()->create();
        $caretaker = User::factory()->create();
        $player->users()->attach($caretaker->id, ['self' => false]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.players.caretakers.store', $player), ['user_id' => $caretaker->id])
            ->assertOk();

        $this->assertSame(1, $player->users()->whereKey($caretaker->id)->count());
    }

    public function test_admin_can_detach_a_caretaker(): void
    {
        $player = Player::factory()->create();
        $caretaker = User::factory()->create();
        $player->users()->attach($caretaker->id, ['self' => false]);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.players.caretakers.destroy', [$player, $caretaker]))
            ->assertOk();

        $this->assertFalse($player->users()->whereKey($caretaker->id)->exists());
    }

    public function test_caretaker_modal_lists_and_offers_users(): void
    {
        $player = Player::factory()->create();
        $attached = User::factory()->create(['name' => 'Eltern', 'lastname' => 'Teil']);
        $player->users()->attach($attached->id, ['self' => false]);
        $other = User::factory()->create(['name' => 'Frei', 'lastname' => 'Wahl']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.players.caretakers', $player))
            ->assertOk();

        $response->assertSee('Eltern Teil');                       // zugeordnet
        $response->assertSee('Frei Wahl');                         // wählbar
        $response->assertSee(route('admin.players.caretakers.destroy', [$player, $attached]), false);
    }

    public function test_non_admin_cannot_manage_caretakers(): void
    {
        $player = Player::factory()->create();
        $registrar = User::factory()->create();
        $registrar->roles()->attach(20); // Bürokrat: kein portal.manage

        $this->actingAs($registrar)
            ->get(route('admin.players.caretakers', $player))
            ->assertForbidden();
    }
}
