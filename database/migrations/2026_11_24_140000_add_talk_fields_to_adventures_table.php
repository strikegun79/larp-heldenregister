<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #31 (Phase 2): Talk-Verknüpfungen am Event und Nextcloud-Konto am Nutzer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            // Gewünschte Talk-Struktur (Häkchen im Reiter „Talk“).
            $table->boolean('talk_plot_thread')->default(false);
            $table->boolean('talk_teamer_room')->default(false);
            // Verknüpfungen in Nextcloud Talk (null = noch nicht eingerichtet).
            $table->unsignedBigInteger('talk_plot_thread_id')->nullable();
            $table->string('talk_plot_thread_title', 200)->nullable();
            $table->string('talk_teamer_room_token', 64)->nullable();
            $table->string('talk_teamer_room_name', 255)->nullable();
            // Letzte Fehlermeldung der Einrichtung/Synchronisierung (nur Anzeige).
            $table->string('talk_error', 500)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            // Nextcloud-Benutzername (per E-Mail gefunden oder vom Admin eingetragen).
            $table->string('nextcloud_user_id', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn([
                'talk_plot_thread', 'talk_teamer_room', 'talk_plot_thread_id', 'talk_plot_thread_title',
                'talk_teamer_room_token', 'talk_teamer_room_name', 'talk_error',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nextcloud_user_id');
        });
    }
};
