<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Event-Kategorien. Ersetzt `event_category`. IDs übernommen
     * (0 = Keine Kategorie). `deleted` -> SoftDeletes.
     */
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name', 50)->default('');
            $table->string('description', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};
