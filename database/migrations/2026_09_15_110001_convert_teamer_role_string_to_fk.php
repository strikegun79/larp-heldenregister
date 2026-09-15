<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * teamer_signups.teamer_role (VARCHAR) → event_role_id (FK auf event_roles).
     * Bestehende Strings werden per beschreibungsgleichem JOIN migriert.
     */
    public function up(): void
    {
        Schema::table('teamer_signups', function (Blueprint $table) {
            $table->unsignedBigInteger('event_role_id')->nullable()->after('user_id');
        });

        // Bestehende String-Werte auf event_roles.id mappen
        DB::statement('
            UPDATE teamer_signups ts
            JOIN event_roles er ON er.description = ts.teamer_role AND er.for_teamer = 1
            SET ts.event_role_id = er.id
        ');

        Schema::table('teamer_signups', function (Blueprint $table) {
            $table->foreign('event_role_id')
                ->references('id')
                ->on('event_roles')
                ->nullOnDelete();

            $table->dropColumn('teamer_role');
        });
    }

    public function down(): void
    {
        Schema::table('teamer_signups', function (Blueprint $table) {
            $table->string('teamer_role', 50)->nullable()->after('user_id');
        });

        DB::statement('
            UPDATE teamer_signups ts
            JOIN event_roles er ON er.id = ts.event_role_id
            SET ts.teamer_role = er.description
        ');

        Schema::table('teamer_signups', function (Blueprint $table) {
            $table->dropForeign(['event_role_id']);
            $table->dropColumn('event_role_id');
        });
    }
};
