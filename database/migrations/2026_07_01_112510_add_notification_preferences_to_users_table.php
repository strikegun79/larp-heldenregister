<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AUTH-13 Erweiterung: Alle E-Mail-Benachrichtigungs-Präferenzen für Teilnehmer und Projektleitung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Teilnehmer-Benachrichtigungen (für alle Nutzer)
            $table->boolean('notify_booking_received')->default(true)->after('notify_new_user');
            $table->boolean('notify_booking_approved')->default(true)->after('notify_booking_received');
            $table->boolean('notify_booking_rejected')->default(true)->after('notify_booking_approved');
            $table->boolean('notify_booking_cancelled')->default(true)->after('notify_booking_rejected');
            $table->boolean('notify_payment_confirmed')->default(true)->after('notify_booking_cancelled');
            $table->boolean('notify_waitlist_promoted')->default(true)->after('notify_payment_confirmed');
            $table->boolean('notify_event_cancelled')->default(true)->after('notify_waitlist_promoted');
            $table->boolean('notify_event_reminder')->default(true)->after('notify_event_cancelled');
            // Projektleitung: Stornierungsmeldungen von Teilnehmern
            $table->boolean('notify_cancellation_report')->default(true)->after('notify_event_reminder');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_booking_received',
                'notify_booking_approved',
                'notify_booking_rejected',
                'notify_booking_cancelled',
                'notify_payment_confirmed',
                'notify_waitlist_promoted',
                'notify_event_cancelled',
                'notify_event_reminder',
                'notify_cancellation_report',
            ]);
        });
    }
};
