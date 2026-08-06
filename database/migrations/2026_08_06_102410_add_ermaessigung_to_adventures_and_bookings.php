<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->decimal('fee_reduced', 8, 2)->nullable()->after('fee');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('ermaessigung')->default(false)->after('paid');
        });
    }

    public function down(): void
    {
        Schema::table('adventures', function (Blueprint $table) {
            $table->dropColumn('fee_reduced');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('ermaessigung');
        });
    }
};
