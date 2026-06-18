<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Von matrix-corporal verwaltete Räume/Spaces. Ersetzt `matrix_managedRoomIds`.
     * PK ist die echte Matrix-Room-ID (z.B. !abc:waldritter-giessen.de).
     */
    public function up(): void
    {
        Schema::create('matrix_managed_rooms', function (Blueprint $table) {
            $table->string('roomid', 120)->primary();
            $table->string('roomname', 50);
            $table->string('roomtype', 50); // Raum | Space
            $table->boolean('default_allow')->default(false);
            $table->boolean('default_deny')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_managed_rooms');
    }
};
