<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wohnort des Spielers (Steckbrief / Teilnehmerlisten).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('players', 'place')) {
            Schema::table('players', function (Blueprint $table) {
                $table->string('place', 100)->nullable()->after('gender');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('players', 'place')) {
            Schema::table('players', function (Blueprint $table) {
                $table->dropColumn('place');
            });
        }
    }
};
