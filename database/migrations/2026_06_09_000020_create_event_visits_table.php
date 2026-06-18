<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tatsächliche Teilnahme an einem Abenteuer. Ersetzt `event_visit`.
     */
    public function up(): void
    {
        Schema::create('event_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['adventure_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_visits');
    }
};
