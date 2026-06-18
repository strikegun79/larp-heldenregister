<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verknüpft eine EP-Buchung optional mit einem Abenteuer (HERO-11):
     * Buchungen vom Typ „Abenteuer bestritten" (50) erhalten so eine
     * Referenz auf das Event, woraus die Abenteuerhistorie je Held entsteht.
     */
    public function up(): void
    {
        Schema::table('ep_transactions', function (Blueprint $table) {
            $table->foreignId('adventure_id')->nullable()->after('hero_id')
                ->constrained('adventures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ep_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adventure_id');
        });
    }
};
