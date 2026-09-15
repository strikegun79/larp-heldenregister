<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_roles', function (Blueprint $table) {
            $table->boolean('for_participant')->default(true)->after('description');
            $table->boolean('for_teamer')->default(false)->after('for_participant');
        });

        // Teamer-Rollen (IDs 3, 4, 5) korrekt setzen
        DB::table('event_roles')->whereIn('id', [3, 4, 5])->update([
            'for_participant' => false,
            'for_teamer'      => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('event_roles', function (Blueprint $table) {
            $table->dropColumn(['for_participant', 'for_teamer']);
        });
    }
};
