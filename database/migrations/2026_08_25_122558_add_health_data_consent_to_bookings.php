<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // DSGVO Art. 9: Zeitstempel der ausdrücklichen Einwilligung für Gesundheitsdaten.
            $table->timestamp('health_data_consent_at')->nullable()->after('medikamente');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('health_data_consent_at');
        });
    }
};
