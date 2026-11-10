<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PUB-10: Pool vorgedruckter Heldenausweis-Codes.
 * Codes sind erst im System aktiv, wenn sie einem Helden zugewiesen wurden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_card_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->foreignId('hero_id')->nullable()->constrained('heroes')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_card_codes');
    }
};
