<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Klickzähler für Links in Startseiten-Nachrichten (MOTD).
 * Datensparsam: nur Summen je Nachricht und Ziel-URL, keine Nutzerzuordnung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motd_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motd_id')->constrained('motds')->cascadeOnDelete();
            $table->string('url', 500);
            // Hash der URL für den eindeutigen Index (URL selbst ist für einen Index zu lang).
            $table->char('url_hash', 64);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();

            $table->unique(['motd_id', 'url_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motd_link_clicks');
    }
};
