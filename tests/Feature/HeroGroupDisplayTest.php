<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Hero;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** GRP-05: Gruppen im Helden-Detail und auf der öffentlichen Seite. */
class HeroGroupDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        return $user;
    }

    public function test_helden_detail_zeigt_gruppen(): void
    {
        $hero  = Hero::factory()->create();
        $group = Group::factory()->create(['name' => 'Waldwächter']);
        $hero->groups()->attach($group);

        $this->actingAs($this->adminUser())
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('Waldwächter');
    }

    public function test_helden_detail_zeigt_gruppenrolle(): void
    {
        $hero  = Hero::factory()->create();
        $group = Group::factory()->create(['name' => 'Ritter']);
        $hero->groups()->attach($group, ['role' => 'Anführer']);

        $this->actingAs($this->adminUser())
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertSee('Anführer');
    }

    public function test_helden_detail_ohne_gruppe_zeigt_kein_gruppen_feld(): void
    {
        $hero = Hero::factory()->create();

        $this->actingAs($this->adminUser())
             ->get(route('heroes.show', $hero))
             ->assertOk()
             ->assertDontSee('Gruppen');
    }

    public function test_öffentliches_profil_zeigt_gruppen(): void
    {
        $hero  = Hero::factory()->create(['public_code' => 'GRPPUB', 'public_visible' => true]);
        $group = Group::factory()->create(['name' => 'Silberklingen']);
        $hero->groups()->attach($group);

        $this->get(route('public.hero', 'GRPPUB'))
             ->assertOk()
             ->assertSee('Silberklingen');
    }

    public function test_öffentliches_profil_zeigt_keinen_spieler_realname(): void
    {
        $hero  = Hero::factory()->create(['public_code' => 'GRPPN2', 'public_visible' => true]);
        $group = Group::factory()->create(['name' => 'TestGruppe']);
        $hero->groups()->attach($group);

        $this->get(route('public.hero', 'GRPPN2'))
             ->assertOk()
             ->assertSee('TestGruppe')
             ->assertDontSee($hero->player?->full_name ?? 'niemand');
    }
}
