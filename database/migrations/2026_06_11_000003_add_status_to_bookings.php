<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anmelde-Status (ADV-18): offen | bestaetigt | abgelehnt | abgemeldet.
     * Plus Abwesenheitsgrund bei „abgemeldet" (krank, nicht_erschienen,
     * unentschuldigt). Bestehende bestätigte Anmeldungen werden backfilled.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'status')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('status', 20)->default('offen')->after('approved_at');
                $table->string('absence_reason', 30)->nullable()->after('status');
            });

            // Bereits freigegebene Anmeldungen als „bestätigt" übernehmen.
            DB::table('bookings')->whereNotNull('approved_at')->update(['status' => 'bestaetigt']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'status')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn(['status', 'absence_reason']);
            });
        }
    }
};
