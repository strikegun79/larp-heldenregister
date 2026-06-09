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

        // Admins dürfen grundsätzlich alles.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }
}
