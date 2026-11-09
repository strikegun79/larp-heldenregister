<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vom Helden gelernte Fertigkeiten. Ersetzt `hero2skill`.
     * `trained` -> trained_at.
     */
    public function up(): void
    {
        Schema::create('hero_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamp('trained_at')->nullable();
            $table->unique(['hero_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_skill');
    }
};
