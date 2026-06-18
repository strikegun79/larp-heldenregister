<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anmeldungen zu einem Abenteuer. Ersetzt `event_booking`
     * (das im Legacy keinen Primärschlüssel hatte). Die int4-Flags
     * werden zu Booleans, `approved` -> approved_at, `created` -> created_at.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_role_id')->default(1)->constrained('event_roles');
            $table->boolean('fotoerlaubnis')->default(false);
            $table->boolean('vegetarier')->default(false);
            $table->boolean('leih_tunika')->default(false);
            $table->boolean('leih_waffe')->default(false);
            $table->boolean('nsc')->default(false);
            $table->boolean('agb')->default(false);
            $table->boolean('paid')->default(false);
            $table->text('allergien')->nullable();
            $table->text('medikamente')->nullable();
            $table->text('erreichbarkeit')->nullable();
            // Wartelisten-Anmeldung (gesetzt, wenn das Event bereits voll war).
            $table->boolean('waitlisted')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['adventure_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
