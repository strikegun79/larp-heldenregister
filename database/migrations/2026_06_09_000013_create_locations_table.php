<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Veranstaltungsorte. Ersetzt die Legacy-Tabelle `location`.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('titel', 100);
            $table->string('gps', 50)->nullable();
            $table->string('plz', 6)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('address', 150)->nullable();
            $table->longText('image')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
