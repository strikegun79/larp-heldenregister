<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SKILL-09: Aktive Helden je Fertigkeit – Modal mit Helden-/Spielerliste.
 */
class SkillHeroesTest extends TestCase
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
        $user->roles()->attach(10); // Admin: portal.manage

        return $user;
    }

    public function test_modal_zeigt_aktive_helden(): void
    {
        $skill  = Skill::factory()->create(['name' => 'Schwertkunst']);
        $active = Hero::factory()->create(['character_name' => 'Aktiver Ritter', 'active' => true, 'died' => null]);
        $active->skills()->attach($skill->id, ['trained_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.skills.heroes', $skill));

        $response->assertOk()->assertSee('Aktiver Ritter');
    }

    public function test_verschollene_helden_werden_nicht_angezeigt(): void
    {
        $skill      = Skill::factory()->create(['name' => 'Bogenschuss']);
        $verschollen = Hero::factory()->create(['character_name' => 'Verschollener', 'active' => false, 'died' => now()]);
        $verschollen->skills()->attach($skill->id, ['trained_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.skills.heroes', $skill));

        $response->assertOk()->assertDontSee('Verschollener');
    }

    public function test_inaktive_helden_werden_nicht_angezeigt(): void
    {
        $skill    = Skill::factory()->create(['name' => 'Magie']);
        $inaktiv  = Hero::factory()->create(['character_name' => 'Inaktiver Held', 'active' => false, 'died' => null]);
        $inaktiv->skills()->attach($skill->id, ['trained_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.skills.heroes', $skill));

        $response->assertOk()->assertDontSee('Inaktiver Held');
    }

    public function test_index_zaehlt_nur_aktive_helden(): void
    {
        $skill      = Skill::factory()->create(['name' => 'Heilkunde']);
        $active     = Hero::factory()->create(['active' => true,  'died' => null]);
        $verschollen = Hero::factory()->create(['active' => false, 'died' => now()]);
        $active->skills()->attach($skill->id,      ['trained_at' => now()]);
        $verschollen->skills()->attach($skill->id, ['trained_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.skills.index'));

        $response->assertOk()->assertSee('1'); // nur ein aktiver Held
    }
}
