<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * subject_id auf string erweitern, damit Modelle mit nicht-numerischen
 * Primärschlüsseln (z. B. MatrixAccount.mxid) ins Audit-Log geschrieben
 * werden können.
 */
return new class extends Migration
{
    public function up(): void
    {
        // VARCHAR(150) deckt Integer-IDs und mxid-Strings (@user:domain) ab.
        DB::statement('ALTER TABLE audit_logs MODIFY COLUMN subject_id VARCHAR(150) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE audit_logs MODIFY COLUMN subject_id BIGINT UNSIGNED NULL');
    }
};
