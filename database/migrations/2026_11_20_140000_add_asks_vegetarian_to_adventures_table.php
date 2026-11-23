<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Event-Einstellung „Nach vegetarischem Essen fragen“: Nur wenn aktiv, enthält das
 * Anmeldeformular das Kästchen „Vegetarisches Essen“ (z. B. bei eintägigen Events
 * ohne Verpflegung abschalten). Standard für neue Events: aus.
 * Bestehende Events werden einmalig auf „an“ gesetzt, damit sich laufende
 * Anmeldungen nicht unbemerkt ändern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->boolean('asks_vegetarian')->default(false)->after('requires_confirmation');
        });

        DB::table('adventures')->update(['asks_vegetarian' => true]);
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn('asks_vegetarian');
        });
    }
};
