<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Charakter-Steckbrief (HERO-09): Freitext-Hintergrund und optionales
     * Avatar-Bild (Pfad auf der „public"-Disk) je Held.
     */
    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            if (! Schema::hasColumn('heroes', 'description')) {
                $table->text('description')->nullable()->after('homeplace');
            }
            if (! Schema::hasColumn('heroes', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropColumn(['description', 'image']);
        });
    }
};
