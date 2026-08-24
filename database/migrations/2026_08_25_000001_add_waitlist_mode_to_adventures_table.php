<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Einmaliges Flag: sobald eine Buchung auf die Warteliste kommt, wird
 * waitlist_mode=true gesetzt und bleibt dauerhaft aktiv. Alle weiteren
 * Buchungen landen ebenfalls auf der Warteliste, damit keine spätere Anmeldung
 * einen Wartelistenplatz "überholt".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->boolean('waitlist_mode')->default(false)->after('waitlist');
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn('waitlist_mode');
        });
    }
};
