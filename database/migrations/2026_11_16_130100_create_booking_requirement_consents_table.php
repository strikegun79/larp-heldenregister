<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nachweis, wann bei einer Anmeldung welcher Anforderung des Auftraggebers
 * zugestimmt wurde (Issue #14). Anforderungen mit Zustimmungen werden nicht
 * gelöscht, sondern nur deaktiviert (FK RESTRICT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_requirement_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_client_requirement_id')->constrained()->restrictOnDelete();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->unique(['booking_id', 'event_client_requirement_id'], 'booking_requirement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requirement_consents');
    }
};
