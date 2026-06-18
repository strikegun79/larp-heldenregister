<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unterschrift bei Teilnahme (ADV-17): base64-PNG (Data-URL), per Tablet/
     * Stift auf dem Check-in-Tab erfasst. Erscheint in der Teilnehmer-PDF.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'signature')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->longText('signature')->nullable()->after('erreichbarkeit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'signature')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
