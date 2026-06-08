<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\HeroClassSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HeroClassSeeder::class, EpTransactionTypeSeeder::class]);
    }

    public function test_guests_cannot_access_the_hero_register(): void
    {
        $this->get(route('heroes.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_the_hero_register(): void
    {
        $hero = Hero::factory()->create(['character_name' => 'Tilix']);

        $this->actingAs(User::factory()->create())
            ->get(route('heroes.index'))
            ->assertOk()
            ->assertSee('Tilix');
    }

    public function test_a_hero_can_be_created_with_classes(): void
    {
        $player = Player::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('heroes.store'), [
                'player_id' => $player->id,
                'character_name' => 'Aldara',
                'homeplace' => 'Loungville',
                'classes' => [1, 4], // warrior + healer
                'active' => '1',
            ]);

        $hero = Hero::firstWhere('character_name', 'Aldara');

        $this->assertNotNull($hero);
        $response->assertRedirect(route('heroes.show', $hero));
        $this->assertEqualsCanonicalizing([1, 4], $hero->classes->pluck('id')->all());
        $this->assertTrue($hero->active);
    }

    public function test_validation_rejects_a_hero_without_a_player(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('heroes.store'), ['character_name' => 'Namenlos'])
            ->assertSessionHasErrors('player_id');
    }

    public function test_ep_balance_nets_credits_and_debits(): void
    {
        $hero = Hero::factory()->create();
        $hero->epTransactions()->create(['ep_transaction_type_id' => 10, 'ep_count' => 20]); // credit
        $hero->epTransactions()->create(['ep_transaction_type_id' => 20, 'ep_count' => 5]);  // debit

        $this->assertEquals(15.0, $hero->fresh()->ep_balance);
    }

    public function test_a_hero_can_be_deleted(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('heroes.destroy', $hero))
            ->assertRedirect(route('heroes.index'));

        $this->assertModelMissing($hero);
    }
}
