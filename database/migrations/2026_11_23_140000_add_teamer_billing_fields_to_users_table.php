<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #19: Angaben für die Teamer-Abrechnung (Aufwandsentschädigung).
 * Werden verschlüsselt gespeichert (encrypted-Cast) → text-Spalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('teamer_tax_number')->nullable()->after('city');
            $table->text('teamer_account_holder')->nullable()->after('teamer_tax_number');
            $table->text('teamer_iban')->nullable()->after('teamer_account_holder');
            $table->text('teamer_bank_name')->nullable()->after('teamer_iban');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['teamer_tax_number', 'teamer_account_holder', 'teamer_iban', 'teamer_bank_name']);
        });
    }
};
