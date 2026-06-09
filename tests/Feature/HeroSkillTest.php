<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroSkillTest extends TestCase
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

    private function skill(int $cost = 5): Skill
    {
        return Skill::create(['name' => 'Probe-Fertigkeit', 'ep_costs' => $cost, 'perl_count' => 0]);
    }

    /** Held mit einer EP-Gutschrift (Typ 10) ausstatten. */
    private function heroWithEp(float $ep): Hero
    {
        $hero = Hero::factory()->create();
        $hero->epTransactions()->create(['ep_transaction_type_id' => 10, 'ep_count' => $ep]);

        return $hero;
    }

    public function test_a_viewer_cannot_learn_a_skill(): void
    {
        $hero = $this->heroWithEp(20);
        $skill = $this->skill();

        $this->actingAs($this->userWithRole(40)) // Spielleiter: kein heldenregister.edit
            ->post(route('heroes.skills.store', $hero), ['skill_id' => $skill->id])
            ->assertForbidden();

        $this->assertSame(0, $hero->skills()->count());
    }

    public function test_registrar_learns_a_skill_and_ep_is_deducted(): void
    {
        $hero = $this->heroWithEp(20);
        $skill = $this->skill(5);

        $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->postJson(route('heroes.skills.store', $hero), ['skill_id' => $skill->id])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertTrue($hero->skills()->whereKey($skill->id)->exists());
        $this->assertEquals(15.0, $hero->fresh()->ep_balance);
        $this->assertDatabaseHas('ep_transactions', [
            'hero_id' => $hero->id,
            'ep_transaction_type_id' => 20,
            'ep_count' => 5,
        ]);
    }

    public function test_cannot_learn_without_enough_ep(): void
    {
        $hero = $this->heroWithEp(2);
        $skill = $this->skill(5);

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.skills.store', $hero), ['skill_id' => $skill->id])
            ->assertStatus(422);

        $this->assertFalse($hero->skills()->whereKey($skill->id)->exists());
    }

    public function test_cannot_learn_the_same_skill_twice(): void
    {
        $hero = $this->heroWithEp(20);
        $skill = $this->skill(5);
        $hero->skills()->attach($skill->id, ['trained_at' => now()]);

        $this->actingAs($this->userWithRole(20))
            ->postJson(route('heroes.skills.store', $hero), ['skill_id' => $skill->id])
            ->assertStatus(422);

        $this->assertSame(1, $hero->skills()->count());
    }
}
