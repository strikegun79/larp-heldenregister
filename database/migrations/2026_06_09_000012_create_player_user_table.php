<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zuordnung Benutzer <-> Spieler. Ersetzt `user2player`.
     * Das Legacy-Feld `self` ("on"/"off") markiert den eigenen Spieler
     * des Benutzers und wird zu einem Boolean.
     */
    public function up(): void
    {
        Schema::create('player_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->boolean('self')->default(false);
            $table->primary(['user_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_user');
    }
};
