<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mitgeführter Held je Anmeldung (ADV-14): bei der Event-Anmeldung wird der
     * aktive Held des Spielers vorausgewählt und hier gespeichert.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'hero_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('hero_id')->nullable()->after('player_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'hero_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('hero_id');
            });
        }
    }
};
