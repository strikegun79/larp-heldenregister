<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * AUTH-05: Klartext-Passwort-Migration.
 */
class LegacyPasswordMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Setzt Klartext-Passwort direkt in DB, umgeht den 'hashed'-Cast. */
    private function setPlaintextPassword(User $user, string $plain): void
    {
        DB::table('users')->where('id', $user->id)->update(['password' => $plain]);
    }

    // --- Command ---

    public function test_command_marks_plaintext_password_as_needs_reset(): void
    {
        $user = User::factory()->create();
        $this->setPlaintextPassword($user, 'geheim123');

        $this->artisan('app:migrate-legacy-passwords', ['--no-mail' => true])
            ->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->password);
        $this->assertTrue($user->needs_password_reset);
    }

    public function test_command_ignores_already_bcrypt_passwords(): void
    {
        $user = User::factory()->create(); // Factory setzt bcrypt-Hash.

        $this->artisan('app:migrate-legacy-passwords', ['--no-mail' => true])
            ->expectsOutputToContain('Keine nicht-bcrypt-Passwörter');

        $user->refresh();
        $this->assertFalse($user->needs_password_reset);
        $this->assertNotNull($user->password);
    }

    public function test_command_ignores_already_flagged_users(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update([
            'password' => null,
            'needs_password_reset' => true,
        ]);

        $this->artisan('app:migrate-legacy-passwords', ['--no-mail' => true])
            ->expectsOutputToContain('Keine nicht-bcrypt-Passwörter');
    }

    public function test_command_dry_run_makes_no_changes(): void
    {
        $user = User::factory()->create();
        $this->setPlaintextPassword($user, 'klartext');

        $this->artisan('app:migrate-legacy-passwords', ['--dry-run' => true])
            ->assertSuccessful();

        $rawPassword = DB::table('users')->where('id', $user->id)->value('password');
        $this->assertEquals('klartext', $rawPassword);
        $user->refresh();
        $this->assertFalse($user->needs_password_reset);
    }

    // --- Login-Flow ---

    public function test_login_shows_reset_hint_for_flagged_user(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update([
            'password' => null,
            'needs_password_reset' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'irgendwas',
        ]);

        $response->assertSessionHasErrors(['email']);
        $error = session('errors')->first('email');
        $this->assertStringContainsString('Passwort vergessen', $error);
    }

    public function test_login_shows_generic_error_for_normal_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'falsch',
        ]);

        $response->assertSessionHasErrors(['email']);
        $error = session('errors')->first('email');
        $this->assertStringNotContainsString('Passwort vergessen', $error);
    }

    // --- Reset-Flow ---

    public function test_password_reset_clears_needs_password_reset_flag(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update([
            'password' => null,
            'needs_password_reset' => true,
        ]);

        $token = Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NeuesPasswort1!',
            'password_confirmation' => 'NeuesPasswort1!',
        ]);

        $user->refresh();
        $this->assertFalse($user->needs_password_reset);
        $this->assertNotNull($user->password);
    }
}
