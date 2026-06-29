<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        'password',
        'needs_password_reset',
        'lastlogin_at',
        'legacy_id',
        'teamer_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'lastlogin_at' => 'datetime',
        'activated' => 'boolean',
        'needs_password_reset' => 'boolean',
        'teamer_notifications' => 'boolean',
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
            'name'              => 'Gelöscht',
            'lastname'          => null,
            'email'             => 'deleted+'.$this->id.'@deleted.invalid',
            'phone'             => null,
            'street'            => null,
            'house_number'      => null,
            'zip'               => null,
            'city'              => null,
            'password'          => \Illuminate\Support\Str::random(64),
            'remember_token'    => null,
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
