<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die Matrix-Integration (matrix-corporal) wurde durch Nextcloud Talk ersetzt.
 * Die Tabellen werden samt Daten gelöscht (Datensparsamkeit, gespeicherte Zugangsdaten).
 * down() legt nur die leeren Tabellen wieder an – die Daten sind nicht wiederherstellbar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('matrix_room_memberships');
        Schema::dropIfExists('matrix_accounts');
        Schema::dropIfExists('matrix_managed_rooms');
    }

    public function down(): void
    {
        Schema::create('matrix_managed_rooms', function (Blueprint $table) {
            $table->string('roomid', 120)->primary();
            $table->string('roomname', 50);
            $table->string('roomtype', 50);
            $table->boolean('default_allow')->default(false);
            $table->boolean('default_deny')->default(false);
            $table->timestamps();
        });

        Schema::create('matrix_accounts', function (Blueprint $table) {
            $table->string('mxid', 120)->primary();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('display_name', 100)->nullable();
            $table->string('avatar_uri', 100)->nullable();
            $table->text('auth_credential')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('forbid_room_creation')->default(true);
            $table->boolean('forbid_encrypted_room_creation')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

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
};
