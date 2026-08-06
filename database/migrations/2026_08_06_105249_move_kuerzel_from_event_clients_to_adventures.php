<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_clients', function (Blueprint $table) {
            $table->dropColumn('kuerzel');
        });

        Schema::table('adventures', function (Blueprint $table) {
            // Pflichtfeld mit Standardwert, damit bestehende Zeilen nicht NULL haben.
            $table->string('kuerzel', 30)->default('JuFölarp')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn('kuerzel');
        });

        Schema::table('event_clients', function (Blueprint $table) {
            $table->string('kuerzel', 20)->nullable()->after('name');
        });
    }
};
