<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auftraggeber eines Events. Ersetzt `event_auftraggeber`.
     * IDs übernommen (1 = Waldritter-Gießen e.V. ...).
     */
    public function up(): void
    {
        Schema::create('event_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_clients');
    }
};
