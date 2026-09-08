<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            // Nutzer-Verknüpfung: nullOnDelete, damit Abos bei Account-Löschung anonym weiterlaufen bis Abmeldung
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            // Double-Opt-in: Token für Bestätigungs-E-Mail, confirmed_at nach Klick gesetzt
            $table->uuid('token')->unique();
            $table->timestamp('confirmed_at')->nullable();
            // Einwilligungstext zum Zeitpunkt der Anmeldung (DSGVO-Nachweispflicht)
            $table->text('consent_text')->nullable();
            $table->timestamp('consented_at')->nullable();
            // Abmeldung: gesetzt statt Row löschen (Statistik + DSGVO-Nachweis)
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique('email');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
