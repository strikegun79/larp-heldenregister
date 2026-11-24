<?php

use App\Models\HeroClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #29: Helden-Schwur je Klasse. Der Bürokrat sagt den Schwur vor der Übergabe
 * des Bändchens vor, die Spielleitung spricht die feierliche Aufnahme.
 * Die Texte aus dem Issue werden für die fünf Klassen eingetragen (vorhandene Texte bleiben).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_classes', function (Blueprint $table) {
            $table->string('oath_title', 100)->nullable();
            $table->text('oath_text')->nullable();
            $table->text('ceremony_text')->nullable();
        });

        HeroClass::fillDefaultOaths();
    }

    public function down(): void
    {
        Schema::table('hero_classes', function (Blueprint $table) {
            $table->dropColumn(['oath_title', 'oath_text', 'ceremony_text']);
        });
    }
};
