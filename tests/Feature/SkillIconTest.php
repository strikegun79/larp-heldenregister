<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SKILL-08: Fertigkeits-Symbol hochladen und löschen.
 */
class SkillIconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin: portal.manage

        return $user;
    }

    public function test_registrar_kann_symbol_hochladen(): void
    {
        $skill = Skill::factory()->create(['icon' => null]);
        $file  = UploadedFile::fake()->image('symbol.png', 200, 200);

        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.skills.icon.store', $skill), ['icon' => $file]);

        $response->assertOk()->assertJsonFragment(['refresh_modal' => true]);

        $skill->refresh();
        $this->assertNotNull($skill->icon);
        $this->assertStringStartsWith('skills/icons/', $skill->icon);
        Storage::disk('public')->assertExists($skill->icon);
    }

    public function test_hochladen_ersetzt_altes_symbol(): void
    {
        Storage::disk('public')->put('skills/icons/old.jpg', 'dummy');
        $skill = Skill::factory()->create(['icon' => 'skills/icons/old.jpg']);
        $file  = UploadedFile::fake()->image('neu.jpg', 100, 100);

        $this->actingAs($this->admin())
            ->postJson(route('admin.skills.icon.store', $skill), ['icon' => $file])
            ->assertOk();

        Storage::disk('public')->assertMissing('skills/icons/old.jpg');
        $skill->refresh();
        $this->assertNotSame('skills/icons/old.jpg', $skill->icon);
    }

    public function test_registrar_kann_symbol_loeschen(): void
    {
        Storage::disk('public')->put('skills/icons/test.jpg', 'dummy');
        $skill = Skill::factory()->create(['icon' => 'skills/icons/test.jpg']);

        $response = $this->actingAs($this->admin())
            ->deleteJson(route('admin.skills.icon.destroy', $skill));

        $response->assertOk()->assertJsonFragment(['refresh_modal' => true]);

        $skill->refresh();
        $this->assertNull($skill->icon);
        Storage::disk('public')->assertMissing('skills/icons/test.jpg');
    }

    public function test_ungueltige_datei_wird_abgelehnt(): void
    {
        $skill = Skill::factory()->create(['icon' => null]);
        $file  = UploadedFile::fake()->create('dokument.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin())
            ->postJson(route('admin.skills.icon.store', $skill), ['icon' => $file])
            ->assertStatus(422);
    }

    public function test_unauthenticated_user_wird_abgewiesen(): void
    {
        $skill = Skill::factory()->create();
        $file  = UploadedFile::fake()->image('symbol.png');

        $this->postJson(route('admin.skills.icon.store', $skill), ['icon' => $file])
            ->assertUnauthorized();
    }
}
