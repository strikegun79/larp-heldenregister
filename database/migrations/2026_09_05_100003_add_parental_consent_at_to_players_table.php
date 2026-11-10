<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // DSGVO Art. 8: Einwilligungszeitpunkt der Erziehungsberechtigten für Minderjährige (H-5).
            $table->timestamp('parental_consent_at')->nullable()->after('health_data_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('parental_consent_at');
        });
    }
};
