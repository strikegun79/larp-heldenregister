<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HERO-06: Klasse hinzufügen kostet EP (Typ 40), Saldo-Schutz, Admin-Override,
 * Entfernen erstattet (Typ 60).
 */
class HeroClassAssignmentTest extends TestCase
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

    private function heroWithEp(int $ep): Hero
    {
        $hero = Hero::factory()->create(['player_id' => Player::factory()->create()->id]);
        if ($ep > 0) {
            $hero->epTransactions()->create([
                'ep_transaction_type_id' => 10, // Initiale EP (Gutschrift)
                'ep_count' => $ep,
                'transacted_at' => now(),
            ]);
        }

        return $hero;
    }

    public function test_adding_a_class_charges_ep_type_40(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 30]);
        $hero = $this->heroWithEp(100);

        $this->actingAs($this->userWithRole(20)) // Bürokrat: heldenregister.edit
            ->postJson(route('heroes.classes.store', $hero), ['hero_class_id' => $class->id])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertTrue($hero->classes()->whereKey($class->id)->exists());
        $this->assertDatabaseHas('ep_transactions', [
            'hero_id' => $hero->id,
            'ep_transaction_type_id' => 40,
            'ep_count' => 30,
        ]);
        $this->assertEquals(70, $hero->fresh()->ep_balance); // 100 − 30
    }

    public function test_cannot_add_class_without_enough_ep(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 200]);
        $hero = $this->heroWithEp(50);

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.classes.store', $hero), ['hero_class_id' => $class->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nicht genug EP für diese Klasse.');

        $this->assertFalse($hero->classes()->whereKey($class->id)->exists());
        $this->assertDatabaseCount('ep_transactions', 1); // nur die Initial-Gutschrift
    }

    public function test_admin_can_override_negative_balance(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 200]);
        $hero = $this->heroWithEp(50);

        $this->actingAs($this->userWithRole(10)) // Admin
            ->postJson(route('heroes.classes.store', $hero), ['hero_class_id' => $class->id])
            ->assertOk();

        $this->assertTrue($hero->classes()->whereKey($class->id)->exists());
        $this->assertEquals(-150, $hero->fresh()->ep_balance); // 50 − 200
    }

    public function test_cannot_add_a_disabled_class(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'ghost', 'name' => 'Geist', 'ep_cost' => 10, 'disabled' => true]);
        $hero = $this->heroWithEp(100);

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.classes.store', $hero), ['hero_class_id' => $class->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Diese Klasse ist deaktiviert.');
    }

    public function test_cannot_add_a_class_twice(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 10]);
        $hero = $this->heroWithEp(100);
        $hero->classes()->attach($class->id);

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.classes.store', $hero), ['hero_class_id' => $class->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Diese Klasse besitzt der Held bereits.');
    }

    public function test_removing_a_class_refunds_ep(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 30]);
        $hero = $this->heroWithEp(100);
        // Klasse regulär hinzufügen (kostet 30 -> Saldo 70).
        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.classes.store', $hero), ['hero_class_id' => $class->id])->assertOk();

        $this->actingAs($this->userWithRole(20))
            ->deleteJson(route('heroes.classes.destroy', [$hero, $class]))
            ->assertOk();

        $this->assertFalse($hero->classes()->whereKey($class->id)->exists());
        $this->assertEquals(100, $hero->fresh()->ep_balance); // 70 + 30 erstattet
        $this->assertDatabaseHas('ep_transactions', [
            'hero_id' => $hero->id,
            'ep_transaction_type_id' => 60, // Gutschrift
            'ep_count' => 30,
        ]);
    }

    public function test_participant_cannot_manage_classes(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 10]);
        $hero = $this->heroWithEp(100);

        $this->actingAs($this->userWithRole(70)) // Teilnehmer: kein heldenregister.edit
            ->postJson(route('heroes.classes.store', $hero), ['hero_class_id' => $class->id])
            ->assertForbidden();
    }
}
