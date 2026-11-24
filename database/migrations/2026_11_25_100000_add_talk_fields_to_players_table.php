<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #31 (Phase 3): Talk-Freigabe je Spieler – Freigabe durch den Bürokraten,
 * Erlaubnis der Eltern (bei U18) und verknüpftes Nextcloud-Konto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->timestamp('talk_approved_at')->nullable();
            $table->foreignId('talk_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('talk_consent_at')->nullable();
            $table->foreignId('talk_consent_by')->nullable()->constrained('users')->nullOnDelete();
            // Prüfsumme der Texte (Bedingungen, Sicherheit, Regeln), denen zugestimmt wurde.
            $table->string('talk_consent_version', 64)->nullable();
            $table->string('nextcloud_user_id', 64)->nullable();
            // Konto vom Heldenregister angelegt (nur solche werden bei Rücknahme deaktiviert).
            $table->boolean('nextcloud_account_created')->default(false);
            $table->timestamp('talk_account_ready_at')->nullable();
            $table->string('talk_error', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropConstrainedForeignId('talk_approved_by');
            $table->dropConstrainedForeignId('talk_consent_by');
            $table->dropColumn([
                'talk_approved_at', 'talk_consent_at', 'talk_consent_version', 'nextcloud_user_id',
                'nextcloud_account_created', 'talk_account_ready_at', 'talk_error',
            ]);
        });
    }
};
