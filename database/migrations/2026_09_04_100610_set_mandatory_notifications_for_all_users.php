<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pflichtbenachrichtigungen für alle bestehenden Nutzer aktivieren.
     * Diese vier Benachrichtigungen können vom Nutzer nicht deaktiviert werden.
     */
    public function up(): void
    {
        DB::table('users')->update([
            'notify_booking_approved'  => true,
            'notify_booking_rejected'  => true,
            'notify_waitlist_promoted' => true,
            'notify_event_cancelled'   => true,
        ]);
    }

    public function down(): void
    {
        // Nicht umkehrbar – Nutzer hätten diese Werte ggf. bewusst gesetzt.
    }
};
