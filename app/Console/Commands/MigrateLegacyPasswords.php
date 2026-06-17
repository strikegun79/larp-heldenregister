<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/**
 * AUTH-05: Erkennt und markiert Legacy-Klartext-Passwörter aus der ETL-Migration.
 * Setzt password=null + needs_password_reset=true und verschickt Reset-Mail.
 */
class MigrateLegacyPasswords extends Command
{
    protected $signature = 'app:migrate-legacy-passwords
                            {--dry-run : Nur anzeigen, keine Änderungen vornehmen}
                            {--no-mail : Keine Reset-Mails versenden}';

    protected $description = 'Markiert Non-bcrypt-Passwörter und schickt betroffenen Nutzern eine Reset-Mail';

    public function handle(): int
    {
        // Bcrypt-Hashes beginnen mit $2y$ oder $2b$.
        $affected = User::whereNotNull('password')
            ->where('needs_password_reset', false)
            ->where(function ($q) {
                $q->where('password', 'not like', '$2y$%')
                    ->where('password', 'not like', '$2b$%');
            })
            ->get();

        if ($affected->isEmpty()) {
            $this->info('Keine nicht-bcrypt-Passwörter gefunden.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'E-Mail', 'Name'],
            $affected->map(fn ($u) => [$u->id, $u->email, "{$u->name} {$u->lastname}"])
        );

        if ($this->option('dry-run')) {
            $this->warn("Dry-Run: {$affected->count()} Konto(en) wären betroffen – keine Änderungen.");

            return self::SUCCESS;
        }

        $mailed = 0;
        $skipped = 0;

        foreach ($affected as $user) {
            // DB::table umgeht den 'hashed'-Cast des User-Models (AUTH-05).
            DB::table('users')->where('id', $user->id)->update([
                'password' => null,
                'needs_password_reset' => true,
                'updated_at' => now(),
            ]);

            if (! $this->option('no-mail') && $user->email) {
                $status = Password::broker()->sendResetLink(['email' => $user->email]);

                if ($status === Password::RESET_LINK_SENT) {
                    $mailed++;
                } else {
                    $this->warn("Reset-Mail für {$user->email} fehlgeschlagen: {$status}");
                    $skipped++;
                }
            }
        }

        $this->info("{$affected->count()} Konto(en) markiert, {$mailed} Reset-Mail(s) versandt, {$skipped} übersprungen.");

        return self::SUCCESS;
    }
}
