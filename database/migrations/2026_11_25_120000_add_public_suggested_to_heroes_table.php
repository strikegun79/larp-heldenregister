<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Opt-in: Held wird im öffentlichen Heldenverzeichnis unter „Bekannten Gestalten begegnen“ vorgeschlagen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->boolean('public_suggested')->default(false)->after('public_searchable');
        });
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropColumn('public_suggested');
        });
    }
};
