<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klassen eines Helden. Ersetzt `hero2classes`.
     * Hinweis ETL: Legacy `class_id` ist ein String (type_classes.idname,
     * z.B. 'healer'); bei der Migration über hero_classes.slug auflösen.
     */
    public function up(): void
    {
        Schema::create('hero_hero_class', function (Blueprint $table) {
            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hero_class_id')->constrained('hero_classes')->cascadeOnDelete();
            $table->primary(['hero_id', 'hero_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_hero_class');
    }
};
