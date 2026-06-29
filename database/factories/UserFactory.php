<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
        return $this->afterCreating(function (\App\Models\User $user) {
            $role = \App\Models\Role::where('slug', 'admin')->first();
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
        return $this->afterCreating(function (\App\Models\User $user) {
            $role = \App\Models\Role::where('slug', 'teamer')->first();
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
        return $this->afterCreating(function (\App\Models\User $user) use ($slug) {
            $role = \App\Models\Role::where('slug', $slug)->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }
}
