<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zeitpunkt des Erinnerungsversands je Event (NOTI-05) – verhindert
     * Doppelversand (Idempotenz des Scheduled Commands).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('adventures', 'reminder_sent_at')) {
            Schema::table('adventures', function (Blueprint $table) {
                $table->timestamp('reminder_sent_at')->nullable()->after('event_status_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('adventures', 'reminder_sent_at')) {
            Schema::table('adventures', function (Blueprint $table) {
                $table->dropColumn('reminder_sent_at');
            });
        }
    }
};
