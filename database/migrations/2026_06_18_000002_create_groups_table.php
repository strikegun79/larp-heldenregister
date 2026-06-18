<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('group_hero', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('hero_id');
            // Gruppenrolle (Anführer/Mitglied) – optional, für GRP-03
            $table->string('role', 50)->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->primary(['group_id', 'hero_id']);
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('hero_id')->references('id')->on('heroes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_hero');
        Schema::dropIfExists('groups');
    }
};
