<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_a_viewer_cannot_book_ep(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(40)) // Spielleiter: kein heldenregister.edit
            ->post(route('heroes.ep.store', $hero), ['ep_count' => 10, 'ep_transaction_type_id' => 10])
            ->assertForbidden();

        $this->assertSame(0, $hero->epTransactions()->count());
    }

    public function test_registrar_books_a_credit_and_balance_grows(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20)) // Bürokrat: heldenregister.edit
            ->postJson(route('heroes.ep.store', $hero), [
                'ep_count' => 20,
                'ep_transaction_type_id' => 10, // Initiale EP (Gutschrift)
            ])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertEquals(20.0, $hero->fresh()->ep_balance);
    }

    public function test_a_debit_type_reduces_the_balance(): void
    {
        $hero = Hero::factory()->create();
        $hero->epTransactions()->create(['ep_transaction_type_id' => 10, 'ep_count' => 20]); // +20

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.ep.store', $hero), [
                'ep_count' => 5,
                'ep_transaction_type_id' => 20, // Fertigkeit erworben (Kosten)
            ])
            ->assertOk();

        $this->assertEquals(15.0, $hero->fresh()->ep_balance);
    }

    public function test_validation_requires_amount_and_type(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.ep.store', $hero), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ep_count', 'ep_transaction_type_id']);
    }
}
