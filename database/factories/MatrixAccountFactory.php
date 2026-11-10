<?php

namespace Database\Factories;

use App\Models\MatrixAccount;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MatrixAccount>
 */
class MatrixAccountFactory extends Factory
{
    protected $model = MatrixAccount::class;

    public function definition(): array
    {
        $domain    = config('matrix.domain', 'waldritter-giessen.de');
        $firstname = mb_strtolower(fake('de_DE')->firstName());
        $lastname  = mb_strtolower(fake('de_DE')->lastName());

        return [
            'mxid'                          => "@{$firstname}.{$lastname}:{$domain}",
            'player_id'                     => Player::factory(),
            'display_name'                  => ucfirst($firstname).' '.ucfirst($lastname),
            'avatar_uri'                    => null,
            'auth_credential'               => fake()->password(12, 16),
            'active'                        => true,
            'forbid_room_creation'          => true,
            'forbid_encrypted_room_creation' => true,
        ];
    }

    /** Konto deaktiviert. */
    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
