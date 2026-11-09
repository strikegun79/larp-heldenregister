<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raum-Mitgliedschaften eines Matrix-Kontos. Ersetzt `matrix_joinedRoomIds`.
     * Liefert die joinedRoomIds je User in der corporal-Policy.
     */
    public function up(): void
    {
        Schema::create('matrix_room_memberships', function (Blueprint $table) {
            $table->id();
            $table->string('mxid', 120);
            $table->string('roomid', 120);
            $table->timestamps();

            $table->foreign('mxid')->references('mxid')->on('matrix_accounts')->cascadeOnDelete();
            $table->foreign('roomid')->references('roomid')->on('matrix_managed_rooms')->cascadeOnDelete();
            $table->unique(['mxid', 'roomid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_room_memberships');
    }
};
