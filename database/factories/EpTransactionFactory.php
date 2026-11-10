<?php

namespace Database\Factories;

use App\Models\Hero;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EpTransaction>
 */
class EpTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hero_id'                 => Hero::factory(),
            'ep_transaction_type_id'  => 60, // "Allgemein" (Gutschrift)
            'ep_count'                => fake()->randomFloat(0, 1, 10),
            'transacted_at'           => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /** Gutschrift (is_credit = true). */
    public function credit(int $typeId = 60): static
    {
        return $this->state(fn () => ['ep_transaction_type_id' => $typeId]);
    }

    /** Abzug (is_credit = false). */
    public function debit(int $typeId = 70): static
    {
        return $this->state(fn () => ['ep_transaction_type_id' => $typeId]);
    }

    /** Abenteuer-EP (Typ 50, Gutschrift). */
    public function adventure(): static
    {
        return $this->state(fn () => ['ep_transaction_type_id' => 50]);
    }

    /** Initiale EP (Typ 10). */
    public function initial(): static
    {
        return $this->state(fn () => [
            'ep_transaction_type_id' => 10,
            'ep_count'               => 5,
        ]);
    }
}
