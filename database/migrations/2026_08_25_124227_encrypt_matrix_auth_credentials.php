<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DSGVO K-1: Matrix-Passwörter mit Application-Level Encryption absichern.
 * Spalte auf TEXT verbreitern (verschlüsselte Werte ~250 Zeichen),
 * dann alle bestehenden Klartextwerte verschlüsseln.
 * Nach der Migration nutzt MatrixAccount::$casts ['auth_credential' => 'encrypted'].
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Spalte verbreitern – varchar(100) reicht nicht für verschlüsselte Werte.
        Schema::table('matrix_accounts', function (Blueprint $table) {
            $table->text('auth_credential')->nullable()->change();
        });

        // 2. Bestehende Klartextwerte verschlüsseln.
        DB::table('matrix_accounts')
            ->whereNotNull('auth_credential')
            ->lazyById(100, 'mxid')
            ->each(function (object $account) {
                DB::table('matrix_accounts')
                    ->where('mxid', $account->mxid)
                    ->update(['auth_credential' => Crypt::encryptString($account->auth_credential)]);
            });
    }

    public function down(): void
    {
        // Entschlüsseln und zurück zu varchar(100).
        DB::table('matrix_accounts')
            ->whereNotNull('auth_credential')
            ->lazyById(100, 'mxid')
            ->each(function (object $account) {
                try {
                    $plain = Crypt::decryptString($account->auth_credential);
                    DB::table('matrix_accounts')
                        ->where('mxid', $account->mxid)
                        ->update(['auth_credential' => $plain]);
                } catch (\Exception) {
                    // War schon Klartext oder ungültig – unverändert lassen.
                }
            });

        Schema::table('matrix_accounts', function (Blueprint $table) {
            $table->string('auth_credential', 100)->nullable()->change();
        });
    }
};
