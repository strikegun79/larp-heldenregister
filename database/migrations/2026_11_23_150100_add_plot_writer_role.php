<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Issue #30: Neue Rolle „Plot-Schreiber“ für bestehende Datenbanken anlegen
 * (neue Datenbanken erhalten sie zusätzlich über den RoleSeeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('roles')->where('id', 35)->exists()) {
            DB::table('roles')->insert([
                'id' => 35,
                'slug' => 'plot_writer',
                'label' => 'Plot-Schreiber',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_user')->where('role_id', 35)->delete();
        DB::table('roles')->where('id', 35)->delete();
    }
};
