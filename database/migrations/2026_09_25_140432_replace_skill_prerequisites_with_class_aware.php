<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SKILL-10: Voraussetzungen werden jetzt pro Klasse gespeichert.
 * Neue Tabelle skill_class_prerequisites ersetzt skill_prerequisites.
 * Bestehende Daten werden pro Klasse kopiert (eine globale Voraussetzung
 * wird für jede Klasse, in der die Fertigkeit vorkommt, dupliziert).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_class_prerequisites', function (Blueprint $table) {
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hero_class_id')->constrained('hero_classes')->cascadeOnDelete();
            $table->foreignId('required_skill_id')->constrained('skills')->cascadeOnDelete();
            $table->primary(['skill_id', 'hero_class_id', 'required_skill_id']);
        });

        // Bestehende globale Voraussetzungen für jede Klassen-Zuordnung der Fertigkeit kopieren.
        DB::statement("
            INSERT IGNORE INTO skill_class_prerequisites (skill_id, hero_class_id, required_skill_id)
            SELECT sp.skill_id, shc.hero_class_id, sp.required_skill_id
            FROM skill_prerequisites sp
            JOIN skill_hero_class shc ON shc.skill_id = sp.skill_id
        ");

        Schema::dropIfExists('skill_prerequisites');
    }

    public function down(): void
    {
        Schema::create('skill_prerequisites', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_id');
            $table->unsignedBigInteger('required_skill_id');
            $table->primary(['skill_id', 'required_skill_id']);
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
            $table->foreign('required_skill_id')->references('id')->on('skills')->cascadeOnDelete();
        });

        // Daten zurückkopieren (dedupliziert, Klassen-Kontext geht verloren).
        DB::statement("
            INSERT IGNORE INTO skill_prerequisites (skill_id, required_skill_id)
            SELECT DISTINCT skill_id, required_skill_id FROM skill_class_prerequisites
        ");

        Schema::dropIfExists('skill_class_prerequisites');
    }
};
