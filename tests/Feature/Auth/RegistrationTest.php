<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Max',
            'lastname' => 'Mustermann',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_new_user_gets_participant_and_event_booking_roles(): void
    {
        $this->post('/register', [
            'name' => 'Max',
            'lastname' => 'Mustermann',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $slugs = $user->roles->pluck('slug')->all();

        $this->assertContains('participant', $slugs);
        $this->assertContains('event_booking', $slugs);
        $this->assertNotContains('admin', $slugs);
    }

    public function test_new_user_is_activated(): void
    {
        $this->post('/register', [
            'name' => 'Max',
            'lastname' => 'Mustermann',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->activated);
    }

    public function test_lastname_is_required(): void
    {
        $this->post('/register', [
            'name' => 'Max',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('lastname');
    }

    public function test_registration_is_rate_limited_after_five_attempts(): void
    {
        // Bewusst falsche Passwort-Bestätigung: Validierung schlägt fehl, kein Login.
        // throttle:5,1 zählt trotzdem, da der Hit vor der Controller-Ausführung erfolgt.
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/register', [
                'name' => 'Spam User',
                'lastname' => 'Test',
                'email' => "spam{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'falsch',
            ]);
        }

        // 6. Versuch muss mit 429 Too Many Requests abgewiesen werden.
        $this->post('/register', [
            'name' => 'Spam User',
            'lastname' => 'Test',
            'email' => 'spam6@example.com',
            'password' => 'password',
            'password_confirmation' => 'falsch',
        ])->assertStatus(429);
    }
}
