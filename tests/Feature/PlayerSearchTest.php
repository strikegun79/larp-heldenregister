<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PLAY-09: Volltext-Suche und Sortierung in eigener + Admin-Spielerliste.
 */
class PlayerSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    // ── Eigene Spielerliste ──────────────────────────────────────────────────

    public function test_own_list_search_by_firstname(): void
    {
        $user = $this->userWithRole(70);
        $match = Player::factory()->create(['name' => 'Adalbert', 'lastname' => 'Ritter']);
        $other = Player::factory()->create(['name' => 'Bertha', 'lastname' => 'Vogel']);
        $user->players()->attach([$match->id => ['self' => false], $other->id => ['self' => false]]);

        $this->actingAs($user)
            ->get(route('players.index', ['q' => 'Adal']))
            ->assertOk()
            ->assertSee('Adalbert')
            ->assertDontSee('Bertha');
    }

    public function test_own_list_search_by_lastname(): void
    {
        $user = $this->userWithRole(70);
        $match = Player::factory()->create(['name' => 'Emil', 'lastname' => 'Schwertfisch']);
        $other = Player::factory()->create(['name' => 'Franz', 'lastname' => 'Hammer']);
        $user->players()->attach([$match->id => ['self' => false], $other->id => ['self' => false]]);

        $this->actingAs($user)
            ->get(route('players.index', ['q' => 'schwert']))
            ->assertOk()
            ->assertSee('Emil')
            ->assertDontSee('Franz');
    }

    public function test_own_list_empty_query_returns_all(): void
    {
        $user = $this->userWithRole(70);
        $a = Player::factory()->create(['name' => 'Anna', 'lastname' => 'Berg']);
        $b = Player::factory()->create(['name' => 'Bodo', 'lastname' => 'Berg']);
        $user->players()->attach([$a->id => ['self' => false], $b->id => ['self' => false]]);

        $this->actingAs($user)
            ->get(route('players.index'))
            ->assertOk()
            ->assertSee('Anna')
            ->assertSee('Bodo');
    }

    // ── Admin-Spielerliste ───────────────────────────────────────────────────

    public function test_admin_list_search_by_name(): void
    {
        Player::factory()->create(['name' => 'Greta', 'lastname' => 'Wald']);
        Player::factory()->create(['name' => 'Hans', 'lastname' => 'Berg']);

        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.players.index', ['q' => 'Greta']))
            ->assertOk()
            ->assertSee('Greta')
            ->assertDontSee('Hans');
    }

    public function test_admin_list_search_by_lastname(): void
    {
        Player::factory()->create(['name' => 'Ida', 'lastname' => 'Fels']);
        Player::factory()->create(['name' => 'Karl', 'lastname' => 'Bach']);

        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.players.index', ['q' => 'fels']))
            ->assertOk()
            ->assertSee('Ida')
            ->assertDontSee('Karl');
    }

    public function test_admin_list_sort_by_lastname_asc(): void
    {
        Player::factory()->create(['name' => 'X', 'lastname' => 'Zimmermann']);
        Player::factory()->create(['name' => 'X', 'lastname' => 'Abt']);

        $response = $this->actingAs($this->userWithRole(10))
            ->get(route('admin.players.index', ['sort' => 'lastname', 'dir' => 'asc']))
            ->assertOk();

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Zimmermann'), strpos($content, 'Abt'));
    }

    public function test_admin_list_sort_by_lastname_desc(): void
    {
        Player::factory()->create(['name' => 'X', 'lastname' => 'Zimmermann']);
        Player::factory()->create(['name' => 'X', 'lastname' => 'Abt']);

        $response = $this->actingAs($this->userWithRole(10))
            ->get(route('admin.players.index', ['sort' => 'lastname', 'dir' => 'desc']))
            ->assertOk();

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Abt'), strpos($content, 'Zimmermann'));
    }

    public function test_admin_list_invalid_sort_column_falls_back_to_name(): void
    {
        // Ungültige Sortierung darf keine SQL-Injection ermöglichen.
        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.players.index', ['sort' => 'DROP TABLE players', 'dir' => 'asc']))
            ->assertOk();
    }

    public function test_admin_list_pagination_preserves_query(): void
    {
        // Paginator-Links sollen q und sort im Query-String behalten.
        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.players.index', ['q' => 'test', 'sort' => 'lastname', 'dir' => 'desc']))
            ->assertOk()
            ->assertSee('q=test', false);
    }
}
