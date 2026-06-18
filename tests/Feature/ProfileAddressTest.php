<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_save_address_in_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Max',
                'lastname' => 'Mustermann',
                'email' => $user->email,
                'phone' => '01234 567890',
                'street' => 'Hauptstraße',
                'house_number' => '12a',
                'zip' => '35390',
                'city' => 'Gießen',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'street' => 'Hauptstraße',
            'house_number' => '12a',
            'zip' => '35390',
            'city' => 'Gießen',
        ]);
    }

    public function test_has_complete_address_returns_true_when_all_fields_set(): void
    {
        $user = User::factory()->create([
            'name' => 'Max',
            'lastname' => 'Mustermann',
            'phone' => '01234',
            'street' => 'Hauptstraße',
            'house_number' => '1',
            'zip' => '35390',
            'city' => 'Gießen',
        ]);

        $this->assertTrue($user->hasCompleteAddress());
    }

    public function test_has_complete_address_returns_false_when_fields_missing(): void
    {
        $user = User::factory()->create([
            'name' => 'Max',
            'lastname' => 'Mustermann',
            'street' => null,
            'house_number' => null,
            'zip' => null,
            'city' => null,
        ]);

        $this->assertFalse($user->hasCompleteAddress());
    }

    public function test_profile_form_shows_address_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Straße')
            ->assertSee('Hausnummer')
            ->assertSee('PLZ')
            ->assertSee('Ort');
    }

    public function test_profile_shows_warning_when_address_incomplete(): void
    {
        $user = User::factory()->create(['street' => null]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Kontaktdaten sind unvollständig');
    }
}
