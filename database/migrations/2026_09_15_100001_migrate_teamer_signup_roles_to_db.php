<?php

use App\Models\EventRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * teamer_signups.teamer_role enthielt bisher hardcodierte Strings.
     * Werte die nicht exakt einer for_teamer-EventRole entsprechen werden
     * auf null gesetzt – der Projektleiter kann sie über das Dropdown neu zuweisen.
     */
    public function up(): void
    {
        $validDescriptions = EventRole::forTeamer()->pluck('description');

        DB::table('teamer_signups')
            ->whereNotNull('teamer_role')
            ->whereNotIn('teamer_role', $validDescriptions)
            ->update(['teamer_role' => null]);
    }

    public function down(): void
    {
        // Ursprüngliche Strings nicht wiederherstellbar.
    }
};
