<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_notifies_admins_and_sends_verification_mail(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->roles()->attach(10); // admin

        $this->post('/register', [
            'name' => 'Neuer',
            'email' => 'neuer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $newUser = User::firstWhere('email', 'neuer@example.com');

        // Admin wird über die neue Registrierung informiert.
        Notification::assertSentTo($admin, NewUserRegistered::class);

        // Der neue Benutzer erhält die Aktivierungs-/Verifizierungsmail.
        Notification::assertSentTo($newUser, VerifyEmail::class);
    }

    public function test_non_admins_are_not_notified(): void
    {
        Notification::fake();

        $participant = User::factory()->create();
        $participant->roles()->attach(70); // participant

        $this->post('/register', [
            'name' => 'Noch Einer',
            'email' => 'noch.einer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Notification::assertNotSentTo($participant, NewUserRegistered::class);
    }
}
