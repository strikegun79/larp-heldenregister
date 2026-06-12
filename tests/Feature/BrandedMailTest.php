<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * NOTI-06: Deutsche, gebrandete Auth-Mails + gemeinsames Mail-Layout.
 */
class BrandedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_is_german(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null]);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Bestätige deine E-Mail-Adresse'
                && $mail->actionText === 'E-Mail-Adresse bestätigen'
                && str_contains((string) $mail->salutation, 'Viele Grüße');
        });
    }

    public function test_reset_password_mail_is_german(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $user->sendPasswordResetNotification('test-token');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Passwort zurücksetzen'
                && $mail->actionText === 'Passwort zurücksetzen';
        });
    }

    public function test_verify_mail_renders_with_branded_layout(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $mail = (new VerifyEmail)->toMail($user);

        // Volle HTML-Ausgabe über den Markdown-Renderer (registriert mail::-Komponenten).
        $html = (string) app(\Illuminate\Mail\Markdown::class)->render('notifications::email', $mail->data());

        $this->assertStringContainsString('Waldritter-Gießen e.V.', $html); // Header/Footer-Branding
        $this->assertStringContainsString('E-Mail-Adresse bestätigen', $html);
        $this->assertStringContainsString('Viele Grüße', $html);
    }

    public function test_footer_view_is_branded(): void
    {
        $html = view('vendor.mail.html.footer', ['slot' => ''])->render();

        $this->assertStringContainsString('Waldritter-Gießen e.V.', $html);
    }
}
