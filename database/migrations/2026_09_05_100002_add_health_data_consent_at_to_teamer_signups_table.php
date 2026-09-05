<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teamer_signups', function (Blueprint $table) {
            // DSGVO Art. 9: Einwilligungszeitpunkt für Gesundheitsdaten bei Teamer-Anmeldungen (H-2).
            $table->timestamp('health_data_consent_at')->nullable()->after('medikamente');
        });
    }

    public function down(): void
    {
        Schema::table('teamer_signups', function (Blueprint $table) {
            $table->dropColumn('health_data_consent_at');
        });
    }
};
