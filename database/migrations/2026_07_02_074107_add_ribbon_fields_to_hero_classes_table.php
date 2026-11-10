<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hero_classes', function (Blueprint $table) {
            // Hex-Farbcode für das Klassenband (z. B. '#c0392b').
            $table->string('ribbon_color', 7)->nullable()->after('ep_cost');
            // Pfad zum hochgeladenen Bandmuster-Bild (public-Disk, 162×600 px).
            $table->string('ribbon_image')->nullable()->after('ribbon_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hero_classes', function (Blueprint $table) {
            $table->dropColumn(['ribbon_color', 'ribbon_image']);
        });
    }
};
