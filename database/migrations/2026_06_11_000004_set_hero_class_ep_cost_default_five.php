<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HERO-20: Standard-EP-Kosten für das Hinzufügen einer Klasse = 5 EP
     * (vorher pauschal 50). Bestehende Klassen mit dem alten Default werden
     * übernommen; individuell angepasste Werte bleiben unberührt.
     */
    public function up(): void
    {
        if (Schema::hasColumn('hero_classes', 'ep_cost')) {
            DB::statement('ALTER TABLE hero_classes ALTER COLUMN ep_cost SET DEFAULT 5');
            DB::table('hero_classes')->where('ep_cost', 50)->update(['ep_cost' => 5]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hero_classes', 'ep_cost')) {
            DB::statement('ALTER TABLE hero_classes ALTER COLUMN ep_cost SET DEFAULT 50');
        }
    }
};
