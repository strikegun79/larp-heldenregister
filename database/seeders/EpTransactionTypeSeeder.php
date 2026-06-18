<?php

namespace Database\Seeders;

use App\Models\EpTransactionType;
use Illuminate\Database\Seeder;

class EpTransactionTypeSeeder extends Seeder
{
    /**
     * EP-Buchungsarten aus dem Legacy-System (type_transEP). IDs erhalten.
     * is_credit = true entspricht dem Legacy-Typ "EP erworben".
     */
    public function run(): void
    {
        $types = [
            ['id' => 10, 'description' => 'Initiale EP',         'is_credit' => true],
            ['id' => 20, 'description' => 'Fertigkeit erworben', 'is_credit' => false],
            ['id' => 30, 'description' => 'Bändchen verloren',   'is_credit' => false],
            ['id' => 40, 'description' => 'Klasse hinzugefügt',  'is_credit' => false],
            ['id' => 50, 'description' => 'Abenteuer bestritten', 'is_credit' => true],
            ['id' => 60, 'description' => 'Allgemein',           'is_credit' => true],
            ['id' => 70, 'description' => 'Allgemein',           'is_credit' => false],
        ];

        foreach ($types as $type) {
            EpTransactionType::updateOrCreate(['id' => $type['id']], $type);
        }
    }
}
