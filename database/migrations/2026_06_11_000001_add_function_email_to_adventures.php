<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Funktions-E-Mail des Events (ADV-15): zentrale Kontakt-/Funktionsadresse,
     * die im Event-Detail angezeigt wird (z. B. orga@…).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('adventures', 'function_email')) {
            Schema::table('adventures', function (Blueprint $table) {
                $table->string('function_email')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('adventures', 'function_email')) {
            Schema::table('adventures', function (Blueprint $table) {
                $table->dropColumn('function_email');
            });
        }
    }
};
