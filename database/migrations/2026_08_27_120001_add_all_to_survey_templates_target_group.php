<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE survey_templates MODIFY COLUMN target_group ENUM('participant_child','participant_teen','teamer','parent','all') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE survey_templates MODIFY COLUMN target_group ENUM('participant_child','participant_teen','teamer','parent') NOT NULL");
    }
};
