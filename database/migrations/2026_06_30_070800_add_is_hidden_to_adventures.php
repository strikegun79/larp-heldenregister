<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            // Manuelles Ausblenden für Nicht-Verwalter (Teilnehmer/Teamer).
            $table->boolean('is_hidden')->default(false)->after('waitlist');
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};
