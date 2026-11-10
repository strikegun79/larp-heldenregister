<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // notify_booking_received ist jetzt Pflichtbenachrichtigung – für alle Nutzer aktivieren.
        DB::table('users')->update(['notify_booking_received' => true]);
    }

    public function down(): void
    {
        // Kein Rollback: der Wert war bereits standardmäßig true.
    }
};
