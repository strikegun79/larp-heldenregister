<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zuordnung Fertigkeit <-> Klasse. Ersetzt `skills2class`.
     */
    public function up(): void
    {
        Schema::create('skill_hero_class', function (Blueprint $table) {
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hero_class_id')->constrained('hero_classes')->cascadeOnDelete();
            $table->primary(['skill_id', 'hero_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_hero_class');
    }
};
