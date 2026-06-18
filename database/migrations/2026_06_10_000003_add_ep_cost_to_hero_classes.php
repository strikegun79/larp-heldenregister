<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * EP-Kosten je Klasse (HERO-06): das nachträgliche Hinzufügen einer Klasse
     * zu einem Helden kostet EP (Legacy: type_transEP 40 „Klasse hinzugefügt").
     * Pauschaler Standardwert, je Klasse im Admin konfigurierbar.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('hero_classes', 'ep_cost')) {
            Schema::table('hero_classes', function (Blueprint $table) {
                $table->unsignedInteger('ep_cost')->default(50)->after('disabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hero_classes', 'ep_cost')) {
            Schema::table('hero_classes', function (Blueprint $table) {
                $table->dropColumn('ep_cost');
            });
        }
    }
};
