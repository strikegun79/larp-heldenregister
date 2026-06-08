<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fertigkeiten. Ersetzt die Legacy-Tabelle `skills`.
     * `masterclass` (Legacy: FK type_classes.id) -> hero_class_id.
     * `perlcolor` (Legacy: FK type_perlcolor.color) -> perl_color_id.
     */
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('ep_costs')->default(0);
            $table->string('level', 50)->nullable();
            $table->foreignId('hero_class_id')->nullable()->constrained('hero_classes')->nullOnDelete();
            $table->foreignId('perl_color_id')->nullable()->constrained('perl_colors')->nullOnDelete();
            $table->unsignedInteger('perl_count')->default(0);
            $table->binary('icon')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
