<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #24: Neue Fragetypen „Optionen mit Freitext“ (choice) und „Smileys 1–5“ (smiley).
 * Der Typ wird von ENUM auf VARCHAR umgestellt, damit weitere Typen ohne
 * Schemaänderung möglich sind; bestehende Werte bleiben erhalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_template_questions', function (Blueprint $table) {
            $table->string('type', 20)->change();
            // Einstellungen je Fragetyp (Optionen, Mehrfachauswahl, Freitext, Smiley-Texte).
            $table->json('options')->nullable()->after('type');
        });

        Schema::table('survey_answers', function (Blueprint $table) {
            // Gewählte Optionen als Text-Schnappschuss (bleibt lesbar, wenn die Vorlage sich ändert).
            $table->json('choice_answer')->nullable()->after('yes_no_answer');
        });
    }

    public function down(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->dropColumn('choice_answer');
        });

        Schema::table('survey_template_questions', function (Blueprint $table) {
            $table->dropColumn('options');
            $table->enum('type', ['rating', 'text', 'yes_no'])->change();
        });
    }
};
