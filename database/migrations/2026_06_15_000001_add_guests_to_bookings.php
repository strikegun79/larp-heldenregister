<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gäste-Anmeldungen (ADV-21): Anmeldungen ohne hinterlegten Spieler.
     * `player_id` wird nullable; Gastdaten (Name/Nachname/Alter/Ort) als Freitext.
     * `booked_by_user_id` merkt den anmeldenden Nutzer (für Sichtbarkeit eigener
     * Gäste und Storno-Info).
     */
    public function up(): void
    {
        // player_id nullable (FK bleibt bestehen; mehrere NULLs sind im Unique-Index erlaubt).
        DB::statement('ALTER TABLE bookings MODIFY player_id BIGINT UNSIGNED NULL');

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'guest_name')) {
                $table->string('guest_name', 100)->nullable()->after('player_id');
                $table->string('guest_lastname', 100)->nullable()->after('guest_name');
                $table->unsignedInteger('guest_age')->nullable()->after('guest_lastname');
                $table->string('guest_place', 100)->nullable()->after('guest_age');
            }
            if (! Schema::hasColumn('bookings', 'booked_by_user_id')) {
                $table->foreignId('booked_by_user_id')->nullable()->after('hero_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'booked_by_user_id')) {
                $table->dropConstrainedForeignId('booked_by_user_id');
            }
            $table->dropColumn(['guest_name', 'guest_lastname', 'guest_age', 'guest_place']);
        });
        DB::statement('ALTER TABLE bookings MODIFY player_id BIGINT UNSIGNED NOT NULL');
    }
};
