<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teamer_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Teamer-Rolle (wird vom Projektleiter zugewiesen, z. B. Spielleitung, Teamer A …)
            $table->string('teamer_role', 50)->nullable();
            $table->text('allergien')->nullable();
            $table->text('medikamente')->nullable();
            $table->string('kontakt_telefon', 50)->nullable();
            $table->boolean('agb')->default(false);
            $table->boolean('leih_tunika')->default(false);
            $table->boolean('leih_waffe')->default(false);
            $table->text('anmerkung')->nullable();
            $table->timestamps();
            // Ein Nutzer kann sich pro Event nur einmal als Teamer anmelden.
            $table->unique(['adventure_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teamer_signups');
    }
};
