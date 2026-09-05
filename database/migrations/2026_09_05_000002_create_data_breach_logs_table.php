<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DSGVO Art. 33/34: Datenpannen-Protokoll.
 * Jede bekannte Datenpanne muss dokumentiert werden – auch wenn keine
 * Meldepflicht gegenüber der Aufsichtsbehörde besteht.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_breach_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('discovered_at');
            $table->text('description');
            $table->json('affected_data_categories');
            $table->unsignedInteger('affected_persons_count')->nullable();
            $table->text('likely_consequences')->nullable();
            $table->text('measures_taken');
            $table->boolean('reportable')->default(false);
            $table->boolean('reported_to_authority')->default(false);
            $table->dateTime('reported_at')->nullable();
            $table->string('authority_reference')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_breach_logs');
    }
};
