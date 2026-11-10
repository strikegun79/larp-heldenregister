<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->tinyInteger('min_age')->unsigned()->nullable()->after('max_player');
            $table->tinyInteger('max_age')->unsigned()->nullable()->after('min_age');
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn(['min_age', 'max_age']);
        });
    }
};
