<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->uuid('token')->unique();
            // Wer wird befragt
            $table->enum('target_type', ['participant_child', 'participant_teen', 'teamer', 'parent']);
            // Optionaler Bezug zu einem Spieler (kann null sein bei Gästen/Teamern ohne Account)
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->timestamp('expires_at')->nullable();
            // Wird gesetzt sobald die Antwort abgegeben wurde → kein zweites Ausfüllen
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['survey_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_links');
    }
};
