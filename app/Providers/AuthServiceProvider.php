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

        // Ein Gate je Berechtigung aus der Rechte-Matrix (config/permissions.php).
        foreach (config('permissions.all', []) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // Den Abenteuer-Bereich darf sehen, wer Events verwaltet ODER buchen kann
        // (Rolle „Event buchen" hat kein events.view, aber adventure.book).
        Gate::define('adventure.access', fn (User $user) => $user->hasPermission('events.view') || $user->hasPermission('adventure.book'));

        // Teilnahme/Check-in erfassen: Spielleiter, Teamer (+ Admin via before).
        Gate::define('manage-attendance', fn (User $user) => $user->hasAnyRole('game_master', 'teamer'));

        // Anmeldungen bestätigen/freigeben (BOOK-05): Bürokrat (+ Admin via before).
        Gate::define('approve-bookings', fn (User $user) => $user->hasRole('registrar'));

        // Teilnahmebeitrag-Status pflegen (BOOK-06): Bürokrat (+ Admin via before).
        Gate::define('manage-payments', fn (User $user) => $user->hasRole('registrar'));

        // Admins dürfen grundsätzlich alles.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }
}
