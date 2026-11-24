<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #28: Standard-EP je Buchungsart (Regelwerk-Vorgabe), wird beim EP-Buchen vorbelegt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ep_transaction_types', function (Blueprint $table) {
            $table->unsignedInteger('default_ep')->nullable()->after('is_credit');
        });
    }

    public function down(): void
    {
        Schema::table('ep_transaction_types', function (Blueprint $table) {
            $table->dropColumn('default_ep');
        });
    }
};
