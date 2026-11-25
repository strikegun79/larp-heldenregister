<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Issue #46: Bereiche einer Umfragevorlage als einzelne Seiten anzeigen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_templates', function (Blueprint $table) {
            $table->boolean('section_pages')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('survey_templates', function (Blueprint $table) {
            $table->dropColumn('section_pages');
        });
    }
};
