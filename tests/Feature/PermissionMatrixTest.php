<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
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

        return $user->fresh();
    }

    public function test_admin_has_every_permission(): void
    {
        $admin = $this->userWithRole(10);

        foreach (config('permissions.all') as $permission) {
            $this->assertTrue($admin->hasPermission($permission), "Admin sollte {$permission} haben");
        }
    }

    /**
     * @dataProvider roleMatrix
     */
    public function test_role_grants_exactly_its_permissions(int $roleId, array $granted): void
    {
        $user = $this->userWithRole($roleId);

        foreach (config('permissions.all') as $permission) {
            $expected = in_array($permission, $granted, true);
            $this->assertSame(
                $expected,
                $user->hasPermission($permission),
                "Rolle {$roleId}: {$permission} sollte ".($expected ? 'erlaubt' : 'verboten').' sein'
            );
        }
    }

    public static function roleMatrix(): array
    {
        return [
            'Bürokrat' => [20, [
                'profile.view', 'player.view', 'heldenregister.view', 'heldenregister.edit',
                'adventure.book', 'adventure.modify', 'adventure.cancel', 'events.view', 'events.edit',
            ]],
            'Projektleitung' => [30, [
                'profile.view', 'player.view', 'heldenregister.view',
                'adventure.book', 'adventure.modify', 'adventure.cancel', 'events.view', 'events.edit',
            ]],
            'Spielleiter' => [40, [
                'profile.view', 'player.view', 'heldenregister.view',
                'adventure.book', 'adventure.modify', 'adventure.cancel', 'events.view',
            ]],
            'Teamer' => [50, [
                'profile.view', 'player.view', 'heldenregister.view',
                'adventure.book', 'adventure.modify', 'adventure.cancel', 'events.view',
            ]],
            'Event buchen' => [60, [
                'profile.view', 'player.view',
                'adventure.book', 'adventure.modify', 'adventure.cancel',
            ]],
            'Teilnehmer' => [70, [
                'profile.view', 'player.view',
            ]],
        ];
    }
}
