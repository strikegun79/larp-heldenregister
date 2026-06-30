<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * REP-01: Aggregierte Helden-Kennzahlen (EP gesamt/ausgegeben, Fertigkeiten, Klassen).
 */
class HeroStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function heroWithData(): Hero
    {
        $hero = Hero::factory()->create(['player_id' => Player::factory()->create()->id]);
        // 100 EP erworben (Typ 10 Gutschrift), 30 EP ausgegeben (Typ 20 Kosten).
        $hero->epTransactions()->create(['ep_transaction_type_id' => 10, 'ep_count' => 100, 'transacted_at' => now()]);
        $hero->epTransactions()->create(['ep_transaction_type_id' => 20, 'ep_count' => 30, 'transacted_at' => now()]);

        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 5]);
        $hero->classes()->attach($class->id);
        $skill = Skill::create(['id' => 1, 'name' => 'Hieb', 'ep_costs' => 10, 'perl_count' => 0]);
        $hero->skills()->attach($skill->id, ['trained_at' => now()]);

        return $hero->fresh(['epTransactions.type', 'classes', 'skills']);
    }

    public function test_aggregated_metrics(): void
    {
        $hero = $this->heroWithData();

        $this->assertEquals(100, $hero->ep_total);
        $this->assertEquals(70, $hero->ep_balance);  // 100 − 30
        $this->assertEquals(30, $hero->ep_spent);    // ausgegeben
        $this->assertSame(1, $hero->skills_count);
        $this->assertSame(1, $hero->classes_count);
    }

    public function test_metrics_shown_in_detail(): void
    {
        $hero = $this->heroWithData();
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat: heldenregister.view

        $this->actingAs($user)
            ->get(route('heroes.show', $hero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('EP gesammelt / ausgegeben')
            ->assertSee('Fertigkeiten / Klassen');
    }
}
