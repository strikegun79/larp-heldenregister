<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_type_definitions', function (Blueprint $table) {
            $table->string('task_type', 50)->primary();
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_days')->default(0);
            $table->enum('default_reference', ['start_at', 'end_at'])->default('start_at');
            $table->enum('default_direction', ['before', 'after'])->default('before');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_type_definitions');
    }
};
