<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #19: Teamer-Abrechnung. Rollen können als abrechnungsfähig markiert werden
 * und eine Standard-Pauschale haben (Vorbelegung für neue Veranstaltungen).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_roles', function (Blueprint $table) {
            $table->boolean('billable')->default(false)->after('for_teamer');
            $table->decimal('default_compensation', 8, 2)->nullable()->after('billable');
        });
    }

    public function down(): void
    {
        Schema::table('event_roles', function (Blueprint $table) {
            $table->dropColumn(['billable', 'default_compensation']);
        });
    }
};
