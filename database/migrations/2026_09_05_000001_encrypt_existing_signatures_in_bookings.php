<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DSGVO Art. 9: Unterschriften (biometrische Daten) nachträglich verschlüsseln.
 * Liest Rohdaten über DB::table() (kein Eloquent-Cast), damit Altdaten nicht als
 * Ciphertext fehlinterpretiert werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->whereNotNull('signature')
            ->orderBy('id')
            ->each(function (object $row): void {
                // Nur verschlüsseln wenn noch Klartext (Data-URL erkennen)
                if (str_starts_with($row->signature, 'data:image/')) {
                    DB::table('bookings')
                        ->where('id', $row->id)
                        ->update(['signature' => encrypt($row->signature)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('bookings')
            ->whereNotNull('signature')
            ->orderBy('id')
            ->each(function (object $row): void {
                try {
                    $plain = decrypt($row->signature);
                    DB::table('bookings')
                        ->where('id', $row->id)
                        ->update(['signature' => $plain]);
                } catch (\Exception) {
                    // Bereits Klartext – überspringen
                }
            });
    }
};
