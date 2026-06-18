<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spieler (reale Person). Ersetzt die Legacy-Tabelle `player`.
     * Das Legacy-Feld `active` ("on"/"off") wird zu einem Boolean,
     * `deleted` zu SoftDeletes. `hero_active` (aktiver Held) wird erst
     * in der heroes-Migration als FK ergänzt (zirkuläre Abhängigkeit).
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable();
            $table->string('lastname', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->date('dayofbirth')->nullable();
            $table->string('gender', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
