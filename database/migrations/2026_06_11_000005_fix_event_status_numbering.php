<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ADV-14: Event-Status-Nummerierung korrigieren –
     * 50 Abrechnung, 60 Abgeschlossen, 70 abgesagt (vorher 50 abgesagt,
     * 60 Abrechnung, 70 Abgeschlossen).
     */
    public function up(): void
    {
        $map = [
            50 => ['description' => 'Abrechnung',   'color' => '#54c8ff'],
            60 => ['description' => 'Abgeschlossen', 'color' => '#23d9c9'],
            70 => ['description' => 'abgesagt',      'color' => '#f2711c'],
        ];
        foreach ($map as $id => $row) {
            DB::table('event_statuses')->where('id', $id)->update($row);
        }
    }

    public function down(): void
    {
        $map = [
            50 => ['description' => 'abgesagt',      'color' => '#f2711c'],
            60 => ['description' => 'Abrechnung',    'color' => '#54c8ff'],
            70 => ['description' => 'Abgeschlossen', 'color' => '#23d9c9'],
        ];
        foreach ($map as $id => $row) {
            DB::table('event_statuses')->where('id', $id)->update($row);
        }
    }
};
