<?php

namespace Tests\Feature;

use App\Models\EpTransactionType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpTransactionTypeAdminTest extends TestCase
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

    public function test_admin_can_list_ep_transaction_types(): void
    {
        EpTransactionType::create(['id' => 10, 'description' => 'Abenteuer', 'is_credit' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.ep-transaction-types.index'))
            ->assertOk()
            ->assertSee('Abenteuer');
    }

    public function test_admin_can_create_an_ep_transaction_type(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.ep-transaction-types.store'), [
                'description' => 'Neuer Typ',
                'is_credit' => true,
            ])
            ->assertOk()
            ->assertJson(['reload' => true]);

        $this->assertDatabaseHas('ep_transaction_types', ['description' => 'Neuer Typ', 'is_credit' => true]);
    }

    public function test_new_type_id_is_max_plus_ten(): void
    {
        EpTransactionType::create(['id' => 50, 'description' => 'Basis', 'is_credit' => false]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.ep-transaction-types.store'), [
                'description' => 'Folge',
                'is_credit' => false,
            ])
            ->assertOk();

        $this->assertDatabaseHas('ep_transaction_types', ['id' => 60, 'description' => 'Folge']);
    }

    public function test_admin_can_update_an_ep_transaction_type(): void
    {
        $type = EpTransactionType::create(['id' => 10, 'description' => 'Alt', 'is_credit' => false]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.ep-transaction-types.update', $type), [
                'description' => 'Neu',
                'is_credit' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('ep_transaction_types', ['id' => 10, 'description' => 'Neu', 'is_credit' => true]);
    }

    public function test_unused_type_can_be_deleted(): void
    {
        $type = EpTransactionType::create(['id' => 10, 'description' => 'Weg', 'is_credit' => false]);

        $this->actingAs($this->admin())
            ->delete(route('admin.ep-transaction-types.destroy', $type))
            ->assertRedirect(route('admin.ep-transaction-types.index'));

        $this->assertDatabaseMissing('ep_transaction_types', ['id' => 10]);
    }

    public function test_non_admin_cannot_manage_ep_transaction_types(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.ep-transaction-types.index'))
            ->assertForbidden();
    }
}
