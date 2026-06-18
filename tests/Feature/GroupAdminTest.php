<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Hero;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GRP-02: Gruppen-CRUD (Verwaltung).
 */
class GroupAdminTest extends TestCase
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

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20);

        return $user;
    }

    private function gameMaster(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(40);

        return $user;
    }

    private function participant(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);

        return $user;
    }

    // Zugriffskontrolle

    public function test_admin_kann_gruppen_verwalten(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.groups.index'))
            ->assertOk();
    }

    public function test_registrar_kann_gruppen_verwalten(): void
    {
        $this->actingAs($this->registrar())
            ->get(route('admin.groups.index'))
            ->assertOk();
    }

    public function test_spielleiter_kann_gruppen_verwalten(): void
    {
        $this->actingAs($this->gameMaster())
            ->get(route('admin.groups.index'))
            ->assertOk();
    }

    public function test_teilnehmer_kann_nicht_auf_gruppen_zugreifen(): void
    {
        $this->actingAs($this->participant())
            ->get(route('admin.groups.index'))
            ->assertForbidden();
    }

    // Liste

    public function test_index_zeigt_gruppen(): void
    {
        Group::factory()->create(['name' => 'Rabenklaue']);

        $this->actingAs($this->admin())
            ->get(route('admin.groups.index'))
            ->assertOk()
            ->assertSee('Rabenklaue');
    }

    // Anlegen

    public function test_admin_kann_gruppe_anlegen(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.groups.store'), [
                'name' => 'Goldene Krone',
                'description' => 'Eine edle Gilde.',
            ])
            ->assertRedirect(route('admin.groups.index'));

        $this->assertDatabaseHas('groups', ['name' => 'Goldene Krone']);
    }

    public function test_store_erfordert_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.groups.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_ajax_create_liefert_formular(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.groups.create'))
            ->assertOk()
            ->assertSee('group-form', false);
    }

    // Bearbeiten

    public function test_admin_kann_gruppe_bearbeiten(): void
    {
        $group = Group::factory()->create(['name' => 'Alt']);

        $this->actingAs($this->admin())
            ->put(route('admin.groups.update', $group), [
                'name' => 'Neu',
                'description' => '',
            ])
            ->assertRedirect(route('admin.groups.index'));

        $this->assertEquals('Neu', $group->fresh()->name);
    }

    public function test_ajax_edit_liefert_formular(): void
    {
        $group = Group::factory()->create(['name' => 'Testgruppe']);

        $this->actingAs($this->admin())
            ->getJson(route('admin.groups.edit', $group))
            ->assertOk()
            ->assertSee('group-form', false)
            ->assertSee('Testgruppe');
    }

    // Löschen

    public function test_admin_kann_gruppe_loeschen(): void
    {
        $group = Group::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.groups.destroy', $group))
            ->assertRedirect(route('admin.groups.index'));

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    public function test_loeschen_entfernt_pivot_eintraege(): void
    {
        $group = Group::factory()->create();
        $hero = Hero::factory()->create();
        $group->heroes()->attach($hero->id);

        $this->actingAs($this->admin())
            ->delete(route('admin.groups.destroy', $group));

        $this->assertDatabaseMissing('group_hero', ['group_id' => $group->id]);
    }
}
