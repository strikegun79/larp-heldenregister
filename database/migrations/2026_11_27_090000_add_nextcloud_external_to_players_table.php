<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Talk: Nextcloud-Konto ist verknüpft, wird aber nicht vom Heldenregister verwaltet
 * (z. B. Nextcloud-Admins, die das technische Konto als Gruppen-Admin nicht verwalten darf).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('nextcloud_external')->default(false)->after('nextcloud_group');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('nextcloud_external');
        });
    }
};
