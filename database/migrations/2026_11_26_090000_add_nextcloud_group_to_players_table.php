<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Talk: Nextcloud-Gruppe, in die das Heldenregister das Konto zuletzt eingeordnet hat
 * (U18- bzw. Volljährigen-Gruppe). Dient dem täglichen Wechsel beim 18. Geburtstag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('nextcloud_group', 64)->nullable()->after('nextcloud_account_created');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('nextcloud_group');
        });
    }
};
