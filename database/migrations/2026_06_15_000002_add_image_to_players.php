<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avatar/Steckbriefbild je Spieler (PLAY-10): Pfad auf der „public"-Disk;
     * ohne Upload wird ein Standardbild angezeigt.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('players', 'image')) {
            Schema::table('players', function (Blueprint $table) {
                $table->string('image')->nullable()->after('gender');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('players', 'image')) {
            Schema::table('players', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
};
