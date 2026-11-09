<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Event-Status. Ersetzt `type_eventStatus`. IDs übernommen
     * (0 = unbekannt ... 70 = Abgeschlossen). `color` für die UI.
     */
    public function up(): void
    {
        Schema::create('event_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('description', 50);
            $table->string('color', 10)->default('#FFFFFF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_statuses');
    }
};
