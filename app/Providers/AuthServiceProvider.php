<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Player::class => \App\Policies\PlayerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Für jede Rolle ein gleichnamiges Gate (z.B. Gate::allows('registrar')).
        foreach (Role::ROLE_SLUGS as $slug) {
            Gate::define($slug, fn (User $user) => $user->hasRole($slug));
        }

        // Menü-/Funktionssichtbarkeit nach Rolle (Allowlist; Admin via Gate::before).
        // Teilnehmer sehen nur Profil + Spieler und tauchen daher in keiner Liste auf.
        Gate::define('view-heldenregister', fn (User $user) => $user->hasAnyRole('registrar', 'game_master', 'teamer'));
        Gate::define('manage-heldenregister', fn (User $user) => $user->hasRole('registrar'));
        // Abenteuer ansehen (Karte/Liste) vs. buchen vs. Events verwalten:
        Gate::define('view-abenteuer', fn (User $user) => $user->hasAnyRole('game_master', 'teamer', 'event_booking'));
        Gate::define('book-abenteuer', fn (User $user) => $user->hasRole('event_booking'));
        // 'manage-events' (Events anlegen/bearbeiten) bleibt Admins vorbehalten
        // (nur über Verwaltung → Veranstaltung). Nicht-Admins erhalten es nie.
        Gate::define('manage-events', fn (User $user) => false);

        // Admins dürfen grundsätzlich alles.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }
}
