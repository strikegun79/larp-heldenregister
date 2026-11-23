<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'email',
        'phone',
        'street',
        'house_number',
        'zip',
        'city',
        // Issue #19: Teamer-Abrechnung (verschlüsselt gespeichert)
        'teamer_tax_number',
        'teamer_account_holder',
        'teamer_iban',
        'teamer_bank_name',
        'password',
        'needs_password_reset',
        'lastlogin_at',
        'legacy_id',
        'teamer_notifications',
        'notify_new_user',
        'notify_booking_received',
        'notify_booking_approved',
        'notify_booking_rejected',
        'notify_booking_cancelled',
        'notify_payment_confirmed',
        'notify_waitlist_promoted',
        'notify_event_cancelled',
        'notify_event_reminder',
        'notify_cancellation_report',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'teamer_tax_number',
        'teamer_account_holder',
        'teamer_iban',
        'teamer_bank_name',
    ];

    /**
     * PHP-seitige Defaults: spiegeln die DB-Defaults (DEFAULT true) wider,
     * damit auch in-memory-Instanzen (new User / factory()->make()) korrekte
     * Notification-Werte haben.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'teamer_notifications' => true,
        'notify_new_user' => true,
        'notify_booking_received' => true,
        'notify_booking_approved' => true,
        'notify_booking_rejected' => true,
        'notify_booking_cancelled' => true,
        'notify_payment_confirmed' => true,
        'notify_waitlist_promoted' => true,
        'notify_event_cancelled' => true,
        'notify_event_reminder' => true,
        'notify_cancellation_report' => true,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // Issue #19: sensible Abrechnungsdaten nur verschlüsselt in der Datenbank
        'teamer_tax_number' => 'encrypted',
        'teamer_account_holder' => 'encrypted',
        'teamer_iban' => 'encrypted',
        'teamer_bank_name' => 'encrypted',
        'lastlogin_at' => 'datetime',
        'activated' => 'boolean',
        'needs_password_reset' => 'boolean',
        'teamer_notifications' => 'boolean',
        'notify_new_user' => 'boolean',
        'notify_booking_received' => 'boolean',
        'notify_booking_approved' => 'boolean',
        'notify_booking_rejected' => 'boolean',
        'notify_booking_cancelled' => 'boolean',
        'notify_payment_confirmed' => 'boolean',
        'notify_waitlist_promoted' => 'boolean',
        'notify_event_cancelled' => 'boolean',
        'notify_event_reminder' => 'boolean',
        'notify_cancellation_report' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Die Rollen des Benutzers (Legacy: user2role).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Die zugeordneten Spieler (Legacy: user2player).
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class)->withPivot('self');
    }

    /**
     * Prüft, ob der Benutzer eine bestimmte Rolle (per Slug) besitzt.
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    /**
     * Prüft, ob der Benutzer mindestens eine der angegebenen Rollen besitzt.
     */
    public function hasAnyRole(string ...$slugs): bool
    {
        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * Admins haben grundsätzlich Zugriff (siehe Gate::before im AuthServiceProvider).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Prüft ob Pflichtdaten der erziehungsberechtigten Person vollständig sind (ORGA-01).
     * Voraussetzung für die Eventanmeldung (ADV-24).
     */
    public function hasCompleteAddress(): bool
    {
        return $this->missingAddressFields() === [];
    }

    /**
     * Gibt die deutschen Labels aller fehlenden Pflichtadressfelder zurück (ADV-24).
     *
     * @return string[]
     */
    public function missingAddressFields(): array
    {
        $missing = [];

        if (! filled($this->name)) {
            $missing[] = 'Vorname';
        }
        if (! filled($this->lastname)) {
            $missing[] = 'Nachname';
        }
        if (! filled($this->phone)) {
            $missing[] = 'Mobil / Telefon';
        }
        if (! filled($this->street)) {
            $missing[] = 'Straße';
        }
        if (! filled($this->house_number)) {
            $missing[] = 'Hausnummer';
        }
        if (! filled($this->zip)) {
            $missing[] = 'PLZ';
        }
        if (! filled($this->city)) {
            $missing[] = 'Ort';
        }

        return $missing;
    }

    /** Issue #19: Wer als Teamer abrechnen kann, sieht im Profil den Bereich „Teamer“. */
    public function canBillAsTeamer(): bool
    {
        return $this->hasAnyRole('teamer', 'lehrmeister', 'project_lead');
    }

    /**
     * Issue #19: Welche Angaben für die Teamer-Abrechnung noch fehlen (leer = vollständig).
     *
     * @return list<string>
     */
    public function missingTeamerBillingFields(): array
    {
        $labels = [
            'street' => 'Straße',
            'house_number' => 'Hausnummer',
            'zip' => 'PLZ',
            'city' => 'Ort',
            'teamer_tax_number' => 'Steuernummer',
            'teamer_account_holder' => 'Kontoinhaber',
            'teamer_iban' => 'IBAN',
            'teamer_bank_name' => 'Name der Bank',
        ];

        return array_values(array_filter($labels, fn ($label, $field) => blank($this->{$field}), ARRAY_FILTER_USE_BOTH));
    }

    /**
     * DSGVO Art. 17: Konto anonymisieren (Recht auf Löschung).
     * Überschreibt alle Klardaten durch Platzhalter und soft-deleted den Datensatz.
     * Audit-Log-Snapshots (actor_name) bleiben erhalten.
     */
    public function anonymize(): void
    {
        $this->tokens()->delete();
        $this->notifications()->delete();

        $this->forceFill([
            'name' => 'Gelöscht',
            'lastname' => null,
            'email' => 'deleted+'.$this->id.'@deleted.invalid',
            'phone' => null,
            'street' => null,
            'house_number' => null,
            'zip' => null,
            'city' => null,
            'teamer_tax_number' => null,
            'teamer_account_holder' => null,
            'teamer_iban' => null,
            'teamer_bank_name' => null,
            'password' => Str::random(64),
            'remember_token' => null,
            'email_verified_at' => null,
        ])->save();

        $this->delete();
    }

    /**
     * Prüft eine Berechtigung anhand der Rollen-Rechte-Matrix
     * (config/permissions.php). 'admin' besitzt über '*' alle Rechte.
     */
    public function hasPermission(string $permission): bool
    {
        $matrix = config('permissions.roles', []);

        foreach ($this->roles as $role) {
            $granted = $matrix[$role->slug] ?? [];
            if (in_array('*', $granted, true) || in_array($permission, $granted, true)) {
                return true;
            }
        }

        return false;
    }
}
