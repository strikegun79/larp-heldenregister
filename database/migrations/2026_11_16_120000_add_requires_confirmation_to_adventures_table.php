<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event-Einstellung „Anmeldungen bestätigen“: Ist sie aktiv, bleiben neue
 * Anmeldungen „offen“, bis die Projektleitung sie bestätigt. Sonst sind
 * Anmeldungen sofort bestätigt. Bestehende Events: aus (Standard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->boolean('requires_confirmation')->default(false)->after('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn('requires_confirmation');
        });
    }
};
