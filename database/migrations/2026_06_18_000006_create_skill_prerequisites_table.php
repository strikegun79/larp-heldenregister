<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_prerequisites', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_id');
            $table->unsignedBigInteger('required_skill_id');
            $table->primary(['skill_id', 'required_skill_id']);
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
            $table->foreign('required_skill_id')->references('id')->on('skills')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_prerequisites');
    }
};
