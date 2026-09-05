<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_id')->constrained()->restrictOnDelete();
            $table->foreignId('survey_template_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
