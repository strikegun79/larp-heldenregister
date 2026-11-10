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

        // ROLE-07: Zusammengesetzte Gates kapseln Oder-Logik aus mehreren Einzelrechten,
        // damit Views/Controller stets @can('name') statt @canany([...]) verwenden.
        // Neue Oder-Verknüpfungen gehören hierher – keine Inline-Prüfungen in Views.

        // Den Abenteuer-Bereich darf sehen, wer Events verwaltet ODER buchen kann
        // (Rolle „Event buchen" hat kein events.view, aber adventure.book).
        Gate::define('adventure.access', fn (User $user) => $user->hasPermission('events.view') || $user->hasPermission('adventure.book'));

        // Teilnahme/Check-in erfassen: Projektleitung, Spielleiter, Lehrmeister, Teamer (+ Admin via before).
        Gate::define('manage-attendance', fn (User $user) => $user->hasAnyRole('project_lead', 'game_master', 'lehrmeister', 'teamer'));

        // Anmeldungen bestätigen/freigeben (BOOK-05): Bürokrat, Projektleitung (+ Admin via before).
        Gate::define('approve-bookings', fn (User $user) => $user->hasAnyRole('registrar', 'project_lead'));

        // Teilnahmebeitrag-Status pflegen (BOOK-06): Bürokrat, Projektleitung (+ Admin via before).
        Gate::define('manage-payments', fn (User $user) => $user->hasAnyRole('registrar', 'project_lead'));

        // Für beliebige Spieler buchen (BOOK-10): Bürokrat, Projektleitung (+ Admin via before);
        // alle anderen dürfen nur eigene/betreute Spieler buchen.
        Gate::define('book-any-player', fn (User $user) => $user->hasAnyRole('registrar', 'project_lead'));

        // Alle Anmeldungen eines Events sehen (ADV-15): Bürokrat, Projektleitung,
        // Spielleiter (+ Admin). Teamer/Event buchen/Teilnehmer sehen nur eigene.
        Gate::define('view-all-bookings', fn (User $user) => $user->hasAnyRole('registrar', 'project_lead', 'game_master'));

        // Unterschriften erfassen + Teilnehmer-PDF (ADV-17): Projektleitung,
        // Bürokrat (+ Admin via before).
        Gate::define('take-signatures', fn (User $user) => $user->hasAnyRole('project_lead', 'registrar'));

        // Check-in/Abmelden je Teilnehmer (ADV-14): nur Projektleitung, Bürokrat
        // (+ Admin via before). Check-in zudem erst ab Status „Anmeldung
        // geschlossen" (siehe Adventure::checkinAllowed()).
        Gate::define('manage-checkin', fn (User $user) => $user->hasAnyRole('project_lead', 'registrar'));

        // Veranstaltungs-Lookups: Portal-Admin ODER Projektleitung (ROLE-10).
        Gate::define('events.admin-access', fn (User $user) =>
            $user->hasPermission('portal.manage') || $user->hasPermission('events.admin'));

        // Helden-Konfiguration: Portal-Admin ODER Spielleiter (ROLE-10).
        Gate::define('heroes.admin-access', fn (User $user) =>
            $user->hasPermission('portal.manage') || $user->hasPermission('heroes.admin'));

        // Heldenausweise: heldenregister.edit (Bürokrat) ODER heroes.admin (Spielleiter).
        Gate::define('id-cards.access', fn (User $user) =>
            $user->hasPermission('heldenregister.edit') || $user->hasPermission('heroes.admin'));

        // Admin-Übersichtsseite zugänglich für alle Rollen mit mind. einem Verwaltungsbereich.
        Gate::define('admin.panel', fn (User $user) => collect([
            'portal.manage', 'newsletter.manage', 'survey.view', 'survey.admin', 'roles.view',
            'events.admin', 'heroes.admin', 'groups.manage',
        ])->contains(fn ($p) => $user->hasPermission($p)));

        // Admins dürfen grundsätzlich alles.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }
}
