<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PUB-01: 6-stelliger eindeutiger Helden-Code für öffentliche Profil-URLs. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->string('public_code', 10)->nullable()->unique()->after('legacy_id');
        });
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropUnique(['public_code']);
            $table->dropColumn('public_code');
        });
    }
};
