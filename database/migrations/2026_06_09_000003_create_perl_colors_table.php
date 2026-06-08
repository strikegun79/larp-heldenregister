<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup der Perlenfarben. Ersetzt die Legacy-Tabelle `type_perlcolor`,
     * die per Hex-Code (varchar) verschlüsselt war. Hier mit eigener ID;
     * `code` hält den Legacy-Schlüssel (Hex oder 'keine') für die ETL-Migration.
     */
    public function up(): void
    {
        Schema::create('perl_colors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perl_colors');
    }
};
