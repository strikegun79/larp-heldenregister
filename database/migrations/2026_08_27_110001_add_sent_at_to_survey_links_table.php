<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_links', function (Blueprint $table) {
            // Dokumentiert, wann die Einladungs-E-Mail versendet wurde (SURV-01).
            $table->timestamp('sent_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('survey_links', function (Blueprint $table) {
            $table->dropColumn('sent_at');
        });
    }
};
