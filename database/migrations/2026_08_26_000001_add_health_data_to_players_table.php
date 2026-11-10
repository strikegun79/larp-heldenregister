<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Gesundheitsdaten am Spielerprofil: werden bei Anmeldungen vorausgefüllt.
            // DSGVO Art. 9: besondere Kategorie – nur mit ausdrücklicher Einwilligung speichern.
            $table->text('allergien')->nullable()->after('city');
            $table->text('medikamente')->nullable()->after('allergien');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['allergien', 'medikamente']);
        });
    }
};
