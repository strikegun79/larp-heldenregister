<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        return $admin;
    }

    public function test_player_address_same_as_guardian_defaults_to_true(): void
    {
        $player = Player::factory()->create();

        $this->assertTrue($player->address_same_as_guardian);
        $this->assertNull($player->street);
    }

    public function test_admin_can_set_child_address(): void
    {
        $player = Player::factory()->create();

        $this->actingAs($this->admin())
            ->putJson(route('admin.players.update', $player), [
                'address_same_as_guardian' => '0',
                'street' => 'Kindstraße',
                'house_number' => '5',
                'zip' => '35390',
                'city' => 'Gießen',
            ])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $player->refresh();
        $this->assertFalse($player->address_same_as_guardian);
        $this->assertEquals('Kindstraße', $player->street);
    }

    public function test_admin_can_reset_to_same_as_guardian(): void
    {
        $player = Player::factory()->create([
            'address_same_as_guardian' => false,
            'street' => 'Alt',
            'house_number' => '1',
            'zip' => '12345',
            'city' => 'Irgendwo',
        ]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.players.update', $player), [
                'address_same_as_guardian' => '1',
            ])
            ->assertOk();

        $this->assertTrue($player->fresh()->address_same_as_guardian);
    }

    public function test_child_address_required_when_not_same_as_guardian(): void
    {
        $player = Player::factory()->create();

        $this->actingAs($this->admin())
            ->putJson(route('admin.players.update', $player), [
                'address_same_as_guardian' => '0',
                'street' => '',
                'house_number' => '',
                'zip' => '',
                'city' => '',
            ])
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_update_player_address(): void
    {
        $player = Player::factory()->create();

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.players.update', $player), [
                'address_same_as_guardian' => '1',
            ])
            ->assertForbidden();
    }
}
