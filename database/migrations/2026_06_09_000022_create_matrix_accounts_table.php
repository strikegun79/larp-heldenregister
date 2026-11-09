<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matrix-Benutzerkonten. Ersetzt `matrix_account`. Diese Tabelle ist die
     * Quelle der Wahrheit für die matrix-corporal-Policy (User-DB des Matrix-Servers).
     * PK ist die Matrix-User-ID (z.B. @marla.ruppel:waldritter-giessen.de).
     * Die Legacy-„true/false"-Strings werden zu Booleans, `password` -> auth_credential
     * (Klartext, da corporal authType=plain nutzt), `deleted` -> SoftDeletes.
     */
    public function up(): void
    {
        Schema::create('matrix_accounts', function (Blueprint $table) {
            $table->string('mxid', 120)->primary();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('display_name', 100)->nullable();
            $table->string('avatar_uri', 100)->nullable();
            $table->string('auth_credential', 100)->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('forbid_room_creation')->default(true);
            $table->boolean('forbid_encrypted_room_creation')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_accounts');
    }
};
