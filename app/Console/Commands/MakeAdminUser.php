<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Weist einem Benutzer die Admin-Rolle zu – z. B. für den ersten Admin-Account
 * einer frischen Installation. Mit --create wird der Benutzer bei Bedarf angelegt.
 */
class MakeAdminUser extends Command
{
    protected $signature = 'user:make-admin
        {email : E-Mail-Adresse des Benutzers}
        {--create : Benutzer anlegen, falls er noch nicht existiert}
        {--name= : Vorname (nur mit --create)}
        {--lastname= : Nachname (nur mit --create)}';

    protected $description = 'Einem Benutzer die Admin-Rolle zuweisen (optional Benutzer anlegen)';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error("Ungültige E-Mail-Adresse: {$email}");

            return self::FAILURE;
        }

        $adminRole = Role::where('slug', 'admin')->first();
        if (! $adminRole) {
            $this->error('Die Rolle „admin“ existiert nicht.');
            $this->line('Bitte zuerst ausführen: php artisan db:seed --class=RoleSeeder');

            return self::FAILURE;
        }

        // Auch gelöschte Benutzer finden, um doppelte E-Mail-Adressen zu vermeiden.
        $user = User::withTrashed()->where('email', $email)->first();

        if ($user?->trashed()) {
            $this->error("Der Benutzer {$email} ist gelöscht und kann nicht zum Admin gemacht werden.");

            return self::FAILURE;
        }

        $created = false;

        if (! $user) {
            if (! $this->option('create')) {
                $this->error("Kein Benutzer mit der E-Mail-Adresse {$email} gefunden.");
                $this->line('Mit --create kann der Benutzer angelegt werden.');

                return self::FAILURE;
            }

            $user = $this->createUser($email);
            if (! $user) {
                return self::FAILURE;
            }
            $created = true;
        }

        if (! $created && $user->hasRole('admin')) {
            $this->info("{$email} ist bereits Admin – keine Änderung.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($user, $adminRole, $created) {
            // Bestehende Rollen bleiben erhalten.
            $user->roles()->syncWithoutDetaching([$adminRole->id]);

            // Ohne bestätigte E-Mail sperrt die 'verified'-Middleware den Zugang.
            $user->forceFill([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'activated' => true,
            ])->save();

            AuditLogger::log('user.make_admin', $user, [
                'role' => 'admin',
                'created' => $created,
                'source' => 'artisan user:make-admin',
            ]);
        });

        $this->info($created
            ? "Admin-Benutzer {$email} wurde angelegt."
            : "{$email} hat jetzt die Admin-Rolle.");

        return self::SUCCESS;
    }

    /**
     * Legt einen neuen Benutzer an. Das Passwort wird immer verdeckt abgefragt,
     * damit es nicht in der Shell-History landet.
     */
    private function createUser(string $email): ?User
    {
        $name = $this->option('name') ?: $this->ask('Vorname');
        $lastname = $this->option('lastname') ?: $this->ask('Nachname');
        $password = $this->secret('Passwort (mind. 8 Zeichen)');
        $confirm = $this->secret('Passwort wiederholen');

        $validator = Validator::make(
            [
                'name' => $name,
                'lastname' => $lastname,
                'password' => $password,
                'password_confirmation' => $confirm,
            ],
            [
                'name' => 'required|string|max:255',
                'lastname' => 'nullable|string|max:255',
                'password' => 'required|string|min:8|confirmed',
            ],
            [
                'name.required' => 'Der Vorname ist erforderlich.',
                'password.required' => 'Das Passwort ist erforderlich.',
                'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
                'password.confirmed' => 'Die Passwörter stimmen nicht überein.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return null;
        }

        // Passwort wird über den 'hashed'-Cast im User-Model gehasht.
        return User::create([
            'name' => $name,
            'lastname' => $lastname,
            'email' => $email,
            'password' => $password,
        ]);
    }
}
