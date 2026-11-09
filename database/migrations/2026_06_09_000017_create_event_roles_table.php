<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teilnahme-Rolle bei einer Buchung. Ersetzt `type_event_role`.
     * IDs übernommen (1 = Spieler, 2 = NSC Elternteil, ...).
     */
    public function up(): void
    {
        Schema::create('event_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('description', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_roles');
    }
};
