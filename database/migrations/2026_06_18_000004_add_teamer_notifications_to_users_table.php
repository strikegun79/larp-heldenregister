<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Opt-out: Teamer/Lehrmeister können Einladungs-Benachrichtigungen abschalten (ADV-28).
            $table->boolean('teamer_notifications')->default(true)->after('activated');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('teamer_notifications');
        });
    }
};
