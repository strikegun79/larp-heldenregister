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
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'email',
        'phone',
        'password',
        'lastlogin_at',
        'legacy_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
