<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Position des Fertigkeits-Buttons auf dem Klassen-Baum-Bild (HERO-16).
     * Je Klasse eigene Position, daher auf der Pivot-Tabelle skill_hero_class
     * (ein Skill kann mehreren Klassen mit unterschiedlicher Position angehören).
     *
     * Idempotent: in manchen Umgebungen wurden die Spalten bereits manuell
     * angelegt (int, default 0).
     */
    public function up(): void
    {
        Schema::table('skill_hero_class', function (Blueprint $table) {
            if (! Schema::hasColumn('skill_hero_class', 'x_percentage')) {
                $table->integer('x_percentage')->default(0)->after('hero_class_id');
            }
            if (! Schema::hasColumn('skill_hero_class', 'y_percentage')) {
                $table->integer('y_percentage')->default(0)->after('x_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('skill_hero_class', function (Blueprint $table) {
            $table->dropColumn(['x_percentage', 'y_percentage']);
        });
    }
};
