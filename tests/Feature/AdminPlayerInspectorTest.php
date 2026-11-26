<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin-Spielerliste mit Inspektor: Zeilen öffnen das Seitenpanel, dort Anschrift,
 * Betreuer und Löschen; AJAX-Antworten als JSON.
 */
class AdminPlayerInspectorTest extends TestCase
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

    public function test_rows_open_inspector_and_keep_only_profile_button(): void
    {
        $player = Player::factory()->create(['name' => 'Mira', 'lastname' => 'Tan', 'dayofbirth' => now()->subYears(10)]);

        $this->actingAs($this->admin())->get(route('admin.players.index'))
            ->assertOk()
            ->assertSee('data-inspector-url="'.route('admin.players.inspector', $player).'"', false)
            ->assertSee('aria-label="Details zu Mira Tan öffnen"', false)
            ->assertSee('kein Held')
            ->assertSee('Profil')
            ->assertDontSee('href="'.route('admin.players.caretakers', $player).'"', false)
            ->assertDontSee('Matrix');
    }

    public function test_inspector_contains_address_caretakers_and_delete(): void
    {
        $player = Player::factory()->create(['name' => 'Mira', 'lastname' => 'Tan']);

        $this->actingAs($this->admin())
            ->get(route('admin.players.inspector', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('data-inspector-title', false)
            ->assertSeeInOrder(['Anschrift', 'Betreuer', 'Spieler löschen'])
            ->assertDontSee('Matrix')
            ->assertSee(route('admin.players.update', $player), false)
            ->assertSee(route('admin.players.caretakers.store', $player), false)
            ->assertSee('data-inspector-close-on-success', false);
    }

    public function test_delete_via_ajax_returns_json_and_blockers_offer_force(): void
    {
        $admin = $this->admin();
        $free = Player::factory()->create();
        $withHero = Player::factory()->create();
        Hero::factory()->for($withHero)->create();

        $this->actingAs($admin)->deleteJson(route('admin.players.destroy', $free->id))
            ->assertOk()->assertJsonStructure(['message']);
        $this->assertSoftDeleted($free);

        $this->actingAs($admin)->deleteJson(route('admin.players.destroy', $withHero->id))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->get(route('admin.players.inspector', $withHero), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertSee('Trotzdem löschen')
            ->assertSee('name="force" value="1"', false);
    }
}
