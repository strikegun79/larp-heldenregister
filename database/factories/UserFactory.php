<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'activated' => true,
            'phone' => fake()->phoneNumber(),
            'street' => fake()->streetName(),
            'house_number' => (string) fake()->numberBetween(1, 99),
            'zip' => fake()->postcode(),
            'city' => fake()->city(),
            // Benachrichtigungs-Defaults explizit setzen, damit make()-Instanzen
            // und DB-gespeicherte Nutzer identisches Verhalten zeigen.
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
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Konto deaktiviert (activated = false).
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'activated' => false,
        ]);
    }

    /**
     * Admin-Rolle zuweisen (nach create() ausgeführt).
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::where('slug', 'admin')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }

    /**
     * Teamer-Rolle zuweisen (nach create() ausgeführt).
     */
    public function teamer(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::where('slug', 'teamer')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }

    /**
     * Beliebige Rolle per Slug zuweisen.
     * Beispiel: User::factory()->withRole('game_master')->create()
     */
    public function withRole(string $slug): static
    {
        return $this->afterCreating(function (User $user) use ($slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }
}
