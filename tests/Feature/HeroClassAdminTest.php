<?php

namespace Tests\Feature;

use App\Models\HeroClass;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HERO-05: Admin-CRUD für den Helden-Klassen-Lookup.
 */
class HeroClassAdminTest extends TestCase
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

    public function test_admin_can_create_a_class_with_next_id(): void
    {
        HeroClass::create(['id' => 7, 'slug' => 'warrior', 'name' => 'Krieger']);

        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.hero-classes.store'), [
                'slug' => 'bard',
                'name' => 'Barde',
            ])
            ->assertRedirect(route('admin.hero-classes.index'));

        $this->assertDatabaseHas('hero_classes', [
            'id' => 8, // max(7) + 1
            'slug' => 'bard',
            'name' => 'Barde',
            'disabled' => false,
        ]);
    }

    public function test_admin_can_rename_and_disable_a_class(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger']);

        $this->actingAs($this->userWithRole(10))
            ->put(route('admin.hero-classes.update', $class), [
                'slug' => 'warrior',
                'name' => 'Recke',
                'disabled' => '1',
            ])
            ->assertRedirect(route('admin.hero-classes.index'));

        $this->assertDatabaseHas('hero_classes', [
            'id' => 1,
            'name' => 'Recke',
            'disabled' => true,
        ]);
    }

    public function test_slug_must_be_unique(): void
    {
        HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger']);

        $this->actingAs($this->userWithRole(10))
            ->post(route('admin.hero-classes.store'), [
                'slug' => 'warrior', // bereits vergeben
                'name' => 'Doppelgänger',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('hero_classes', 1);
    }

    public function test_class_keeps_its_own_slug_on_update(): void
    {
        $class = HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger']);

        $this->actingAs($this->userWithRole(10))
            ->put(route('admin.hero-classes.update', $class), [
                'slug' => 'warrior', // eigener Slug -> kein Unique-Konflikt
                'name' => 'Krieger 2',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_non_admin_cannot_manage_classes(): void
    {
        // Bürokrat verwaltet das Heldenregister, hat aber kein portal.manage.
        $this->actingAs($this->userWithRole(20))
            ->get(route('admin.hero-classes.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole(20))
            ->post(route('admin.hero-classes.store'), ['slug' => 'x', 'name' => 'X'])
            ->assertForbidden();
    }

    public function test_disabled_class_is_hidden_from_hero_creation_form(): void
    {
        HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'AktiveKlasse']);
        HeroClass::create(['id' => 2, 'slug' => 'ghost', 'name' => 'DeaktivierteKlasse', 'disabled' => true]);

        $this->actingAs($this->userWithRole(20)) // Bürokrat: heldenregister.edit
            ->get(route('heroes.create'))
            ->assertOk()
            ->assertSee('AktiveKlasse')
            ->assertDontSee('DeaktivierteKlasse');
    }
}
