<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Talk-Protokoll: Anfragen des Heldenregisters an Nextcloud (Änderungen und Fehler).
 * Ohne Zugangsdaten; wird von dsgvo:prune nach 90 Tagen gelöscht.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talk_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adventure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 190);
            $table->string('method', 10);
            $table->string('endpoint', 255);
            $table->unsignedSmallInteger('status')->nullable();
            $table->boolean('success');
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talk_logs');
    }
};
