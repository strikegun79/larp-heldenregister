<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DSGVO: created_by-Felder nullable machen, damit User::forceDelete() nicht durch
 * bestehende FK-Referenzen in Audit-Tabellen blockiert wird (Art. 17 DSGVO).
 * Die Protokolleinträge selbst bleiben erhalten – lediglich der Bezug zum
 * gelöschten Konto wird gekappt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_breach_logs', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->unsignedBigInteger('created_by_user_id')->nullable()->change();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('id_card_codes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('data_breach_logs', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->unsignedBigInteger('created_by_user_id')->nullable(false)->change();
            $table->foreign('created_by_user_id')->references('id')->on('users');
        });

        Schema::table('id_card_codes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }
};
