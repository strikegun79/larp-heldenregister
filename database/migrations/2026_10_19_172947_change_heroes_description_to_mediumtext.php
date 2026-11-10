<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Steckbrief als formatiertes HTML: TEXT (64 KB) reicht bei 12.000 Zeichen
     * plus Formatierung nicht sicher aus, daher MEDIUMTEXT.
     */
    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->mediumText('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }
};
