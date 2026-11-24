<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #30: Bekannte Plot-Charaktere (z. B. Babayaga, König Goldlocke), die im
 * öffentlichen Heldenverzeichnis (/h) gefunden und erforscht werden können.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_characters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('title', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('public_visible')->default(true);
            $table->timestamps();

            $table->index(['public_visible', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_characters');
    }
};
