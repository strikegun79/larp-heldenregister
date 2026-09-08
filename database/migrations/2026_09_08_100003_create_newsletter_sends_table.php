<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('newsletter_subscriptions')->cascadeOnDelete();
            // Eindeutiger Token pro Versand: ermöglicht 1-Klick-Abmeldung direkt aus der Mail
            $table->uuid('token')->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->unique(['newsletter_id', 'subscription_id']); // Kein Doppelversand an dieselbe Adresse
            $table->index(['newsletter_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_sends');
    }
};
