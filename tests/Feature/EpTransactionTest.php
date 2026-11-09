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

    // EP-02: eigenständiges Buchungsformular

    public function test_ep_create_page_is_accessible_for_editor(): void
    {
        $this->actingAs($this->userWithRole(20))
            ->get(route('ep.create'))
            ->assertOk()
            ->assertSee('EP manuell buchen');
    }

    public function test_ep_create_page_is_forbidden_for_viewer_only(): void
    {
        $this->actingAs($this->userWithRole(40)) // Spielleiter: kein heldenregister.edit
            ->get(route('ep.create'))
            ->assertForbidden();
    }

    public function test_store_manual_books_ep_and_redirects(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->post(route('ep.store-manual'), [
                'hero_id' => $hero->id,
                'ep_count' => 5,
                'ep_transaction_type_id' => 10,
            ])
            ->assertRedirect(route('ep.create'))
            ->assertSessionHas('status');

        $this->assertEquals(5.0, $hero->fresh()->ep_balance);
    }

    public function test_store_manual_requires_hero_id(): void
    {
        $this->actingAs($this->userWithRole(20))
            ->post(route('ep.store-manual'), [
                'ep_count' => 5,
                'ep_transaction_type_id' => 10,
            ])
            ->assertSessionHasErrors('hero_id');
    }

    public function test_store_manual_rejects_nonexistent_hero(): void
    {
        $this->actingAs($this->userWithRole(20))
            ->post(route('ep.store-manual'), [
                'hero_id' => 999999,
                'ep_count' => 5,
                'ep_transaction_type_id' => 10,
            ])
            ->assertSessionHasErrors('hero_id');
    }

    public function test_store_accepts_backdated_transacted_at(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.ep.store', $hero), [
                'ep_count' => 10,
                'ep_transaction_type_id' => 10,
                'transacted_at' => '2024-01-15',
            ])
            ->assertOk();

        $tx = $hero->epTransactions()->first();
        $this->assertEquals('2024-01-15', $tx->transacted_at->toDateString());
    }

    public function test_store_manual_accepts_backdated_transacted_at(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->post(route('ep.store-manual'), [
                'hero_id' => $hero->id,
                'ep_count' => 10,
                'ep_transaction_type_id' => 10,
                'transacted_at' => '2023-06-01',
            ])
            ->assertRedirect(route('ep.create'));

        $tx = $hero->epTransactions()->first();
        $this->assertEquals('2023-06-01', $tx->transacted_at->toDateString());
    }

    public function test_store_manual_rejects_invalid_date(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->post(route('ep.store-manual'), [
                'hero_id' => $hero->id,
                'ep_count' => 5,
                'ep_transaction_type_id' => 10,
                'transacted_at' => 'kein-datum',
            ])
            ->assertSessionHasErrors('transacted_at');
    }
}
