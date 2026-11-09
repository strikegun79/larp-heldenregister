<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'lastname' => 'Mustermann',
                'email' => 'test@example.com',
                'phone' => $user->phone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('Mustermann', $user->lastname);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_lastname_and_phone_can_be_updated(): void
    {
        $user = User::factory()->create(['lastname' => null, 'phone' => null]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'lastname' => 'Mustermann',
                'phone' => '+49 123 456789',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Mustermann', $user->lastname);
        $this->assertSame('+49 123 456789', $user->phone);
    }

    public function test_phone_is_required(): void
    {
        $user = User::factory()->create(['phone' => '123']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => '',
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_lastname_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'lastname' => '',
                'email' => $user->email,
                'phone' => $user->phone,
            ])
            ->assertSessionHasErrors('lastname');
    }

    public function test_phone_max_length_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => str_repeat('0', 51),
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        // User nutzt SoftDeletes (Legacy: portal_user.deleted), daher wird der
        // Datensatz als gelöscht markiert statt physisch entfernt.
        $this->assertSoftDeleted($user);
    }

    public function test_profile_page_shows_assigned_roles(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'participant')->first());

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Deine Rollen')
            ->assertSee('Teilnehmer');
    }

    public function test_profile_page_shows_fallback_when_no_roles_assigned(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Keine Rolle zugewiesen.');
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
