<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #19: Aufwandsentschädigung (Pauschale) je abrechnungsfähiger Rolle und Veranstaltung.
 * Kein Eintrag = für diese Rolle bei dieser Veranstaltung keine Abrechnung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventure_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_role_id')->constrained('event_roles')->cascadeOnDelete();
            $table->decimal('amount', 8, 2);
            $table->timestamps();

            $table->unique(['adventure_id', 'event_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventure_compensations');
    }
};
