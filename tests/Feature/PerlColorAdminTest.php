<?php

namespace Tests\Feature;

use App\Models\PerlColor;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerlColorAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        return $admin;
    }

    public function test_admin_can_list_perl_colors(): void
    {
        PerlColor::create(['code' => '#FF0000', 'name' => 'rot']);

        $this->actingAs($this->admin())
            ->get(route('admin.perl-colors.index'))
            ->assertOk()
            ->assertSee('rot');
    }

    public function test_admin_can_create_a_perl_color(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.perl-colors.store'), ['code' => '#00FF00', 'name' => 'grün'])
            ->assertOk()
            ->assertJson(['reload' => true]);

        $this->assertDatabaseHas('perl_colors', ['name' => 'grün', 'code' => '#00FF00']);
    }

    public function test_admin_can_update_a_perl_color(): void
    {
        $color = PerlColor::create(['code' => '#000000', 'name' => 'alt']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.perl-colors.update', $color), ['code' => '#111111', 'name' => 'neu'])
            ->assertOk()
            ->assertJson(['reload' => true]);

        $this->assertDatabaseHas('perl_colors', ['id' => $color->id, 'name' => 'neu']);
    }

    public function test_unused_color_can_be_deleted(): void
    {
        $color = PerlColor::create(['code' => '#AAAAAA', 'name' => 'weg']);

        $this->actingAs($this->admin())
            ->delete(route('admin.perl-colors.destroy', $color))
            ->assertRedirect(route('admin.perl-colors.index'));

        $this->assertDatabaseMissing('perl_colors', ['id' => $color->id]);
    }

    public function test_non_admin_cannot_manage_perl_colors(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.perl-colors.index'))
            ->assertForbidden();
    }
}
