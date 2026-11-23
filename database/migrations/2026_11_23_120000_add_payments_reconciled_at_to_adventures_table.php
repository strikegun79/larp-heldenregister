<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #5: Datum des letzten Abgleichs der Teilnehmerbezahlungen pro Veranstaltung.
 * Teilnehmer sehen so, bis wann Überweisungen bereits erfasst sind.
 * Leer = noch kein Abgleich eingetragen (bestehende Veranstaltungen bleiben leer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->date('payments_reconciled_at')->nullable()->after('fee_reduced');
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn('payments_reconciled_at');
        });
    }
};
