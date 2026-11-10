<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SKILL-08: icon-Spalte von BLOB (binary) auf Dateipfad (string) umstellen.
     * Legacy-Daten waren binär in der DB – jetzt werden Dateien auf Disk gespeichert.
     */
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->string('icon')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->binary('icon')->nullable()->change();
        });
    }
};
