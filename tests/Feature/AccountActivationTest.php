<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->deactivated()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_deactivated_user_gets_error_on_login(): void
    {
        $user = User::factory()->deactivated()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_activated_user_can_login(): void
    {
        $user = User::factory()->create(); // activated = true per Default

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect();
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();

        // Konto nachträglich deaktivieren – direkte Zuweisung (activated nicht in $fillable).
        $user->activated = false;
        $user->save();
        $user->refresh();

        $response = $this->actingAs($user)->get('/profile');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_unverified_user_cannot_access_portal_routes(): void
    {
        // Registriert aber E-Mail noch nicht bestätigt.
        $user = User::factory()->unverified()->create();

        // Alle wesentlichen Portal-Routen müssen auf Verifikations-Hinweis umleiten.
        foreach (['/players', '/heroes', '/adventures', '/profile'] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertRedirect(route('verification.notice'));
        }
    }
}
