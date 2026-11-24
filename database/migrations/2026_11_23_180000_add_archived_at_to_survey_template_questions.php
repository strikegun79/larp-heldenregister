<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #35: Entfernte Fragen mit vorhandenen Antworten werden archiviert statt gelöscht,
 * damit Vorlagen bearbeitbar bleiben und Auswertungen erhalten bleiben.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_template_questions', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('required');
        });
    }

    public function down(): void
    {
        Schema::table('survey_template_questions', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
