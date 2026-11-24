<?php

namespace Tests\Feature;

use App\Models\EpTransactionType;
use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #28: EP-Buchungsarten mit Standardwert, der beim EP-Buchen vorbelegt wird.
 */
class EpTransactionTypeDefaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_standardwert_wird_in_der_verwaltung_gespeichert_und_angezeigt(): void
    {
        $type = EpTransactionType::orderBy('id')->firstOrFail();
        $admin = $this->userWithRole(10);

        $this->actingAs($admin)
            ->putJson(route('admin.ep-transaction-types.update', $type), [
                'description' => $type->description,
                'is_credit' => $type->is_credit ? '1' : '0',
                'default_ep' => '5',
            ])
            ->assertOk();

        $this->assertSame(5, $type->fresh()->default_ep);

        $this->actingAs($admin)
            ->get(route('admin.ep-transaction-types.index'))
            ->assertOk()
            ->assertSee('Standard-EP');

        // Leeres Feld entfernt den Standardwert wieder.
        $this->actingAs($admin)
            ->putJson(route('admin.ep-transaction-types.update', $type), [
                'description' => $type->description,
                'is_credit' => $type->is_credit ? '1' : '0',
                'default_ep' => '',
            ])
            ->assertOk();

        $this->assertNull($type->fresh()->default_ep);
    }

    public function test_ungueltiger_standardwert_wird_abgelehnt(): void
    {
        $type = EpTransactionType::orderBy('id')->firstOrFail();

        foreach (['0', '-3', 'viel'] as $value) {
            $this->actingAs($this->userWithRole(10))
                ->putJson(route('admin.ep-transaction-types.update', $type), [
                    'description' => $type->description,
                    'is_credit' => '1',
                    'default_ep' => $value,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('default_ep');
        }
    }

    public function test_heldenbuch_zeigt_grund_vor_ep_und_belegt_standardwert_vor(): void
    {
        $types = EpTransactionType::orderBy('id')->get();
        $types[0]->update(['default_ep' => 7]);
        $types[1]->update(['default_ep' => 3]);

        $class = HeroClass::create(['id' => 991, 'name' => 'Testklasse', 'slug' => 'test-991', 'ep_cost' => 0]);
        $hero = Hero::factory()->create();
        $hero->classes()->attach($class->id);

        $html = $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->assertSee('data-ep-type-select', false)
            ->assertSee('data-default-ep="3"', false)
            ->assertSee('Standard 7 EP')
            ->getContent();

        // Dropdown „Grund“ steht vor dem EP-Feld.
        $this->assertLessThan(strpos($html, 'id="ep-count-d"'), strpos($html, 'id="ep-type-d"'));
        // Die vorausgewählte erste Buchungsart ist im EP-Feld vorbelegt.
        $this->assertMatchesRegularExpression('/id="ep-count-d"[^>]*value="7"[^>]*data-ep-auto="7"/', $html);
    }

    public function test_ohne_standardwert_bleibt_das_ep_feld_leer(): void
    {
        $class = HeroClass::create(['id' => 991, 'name' => 'Testklasse', 'slug' => 'test-991', 'ep_cost' => 0]);
        $hero = Hero::factory()->create();
        $hero->classes()->attach($class->id);

        $html = $this->actingAs($this->userWithRole(20))
            ->get(route('heroes.show', $hero))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="ep-count-d"[^>]*value=""/', $html);
        $this->assertStringNotContainsString('data-ep-auto=', $html);
    }
}
