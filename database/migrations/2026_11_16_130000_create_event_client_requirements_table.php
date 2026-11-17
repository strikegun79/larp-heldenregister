<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zusätzliche Anforderungen eines Auftraggebers (Issue #14), z. B. die
 * Einverständniserklärung der Jugendförderung Gießen. Aktive Anforderungen
 * müssen bei der Anmeldung zu jedem Event des Auftraggebers bestätigt werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_client_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_client_id');
            $table->string('title', 150);
            $table->text('description')->nullable();
            // PDF liegt nicht öffentlich auf dem Disk „local“; Download nur über Route.
            $table->string('pdf_path')->nullable();
            $table->string('pdf_name')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('event_client_id')->references('id')->on('event_clients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_client_requirements');
    }
};
