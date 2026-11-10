<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventure_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_id')->constrained()->cascadeOnDelete();
            $table->string('task_type', 50);
            $table->enum('trigger_reference', ['start_at', 'end_at'])->default('start_at');
            $table->enum('trigger_direction', ['before', 'after'])->default('before');
            $table->unsignedSmallInteger('trigger_days')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamp('executed_at')->nullable();
            $table->text('result')->nullable();
            $table->timestamps();

            $table->unique(['adventure_id', 'task_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventure_tasks');
    }
};
